<?php
declare(strict_types=1);

/**
 * Jscriptz LLC.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://mage.jscriptz.com/LICENSE
 *
 ********************************************************************
 *
 * @category   Jscriptz
 * @package    Jscriptz_Subcats
 * @author     Jason Lotzer (jasonlotzer@gmail.com)
 * @copyright  Copyright (c) 2019 Jscriptz LLC. (https://mage.jscriptz.com)
 * @license    https://mage.jscriptz.com/LICENSE.txt
 */


namespace Jscriptz\Subcats\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Jscriptz\Subcats\Model\License\ApiClient;
use JsonException;

/**
 * Backend model for validating and storing Jscriptz Subcats license key,
 * handling free trial countdown, version check, and server-driven news.
 */
class License extends Value
{
    public const VERIFY_URL = 'https://mage.jscriptz.com/rest/V1/jscriptz/license/verify';
    public const UPDATE_URL = 'https://mage.jscriptz.com/rest/V1/jscriptz/license/update';
    public const MODULE_CODE = 'jscriptz_subcats';
    public const TRIAL_DAYS = 30;
    public const LICENSE_ACCOUNT_URL = 'https://mage.jscriptz.com/jscriptz_license/account/';

    /**
     * @var Curl|null
     */
    private $curl;

    /**
     * @var StoreManagerInterface|null
     */
    private $storeManager;

    /**
     * @var WriterInterface|null
     */
    private $configWriter;

    /**
     * @var ModuleListInterface|null
     */
    private $moduleList;

    /**
     * @var ApiClient|null
     */
    private $apiClient = null;

    /**
     * @var ComponentRegistrarInterface|null
     */
    private $componentRegistrar;

    /**
     * @var FileDriver|null
     */
    private $fileDriver;

    /**
     * @var Json|null
     */
    private $jsonSerializer;

    /**
     * Lazily get Curl client.
     *
     * @return Curl
     */
    private function getCurl(): Curl
    {
        if ($this->curl === null) {
            $this->curl = ObjectManager::getInstance()->get(Curl::class);
        }
        return $this->curl;
    }

    /**
     * Lazily get the shared ApiClient.
     *
     * @return ApiClient
     */
    private function getApiClient(): ApiClient
    {
        if ($this->apiClient === null) {
            $this->apiClient = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(ApiClient::class);
        }

        return $this->apiClient;
    }

    /**
     * Lazily get StoreManager.
     *
     * @return StoreManagerInterface
     */
    private function getStoreManager(): StoreManagerInterface
    {
        if ($this->storeManager === null) {
            $this->storeManager = ObjectManager::getInstance()->get(StoreManagerInterface::class);
        }
        return $this->storeManager;
    }

    /**
     * Lazily get config writer.
     *
     * @return WriterInterface
     */
    private function getConfigWriter(): WriterInterface
    {
        if ($this->configWriter === null) {
            $this->configWriter = ObjectManager::getInstance()->get(WriterInterface::class);
        }
        return $this->configWriter;
    }

    /**
     * Lazily get module list.
     *
     * @return ModuleListInterface
     */
    private function getModuleList(): ModuleListInterface
    {
        if ($this->moduleList === null) {
            $this->moduleList = ObjectManager::getInstance()->get(ModuleListInterface::class);
        }
        return $this->moduleList;
    }

    /**
     * Lazily get component registrar.
     *
     * @return ComponentRegistrarInterface
     */
    private function getComponentRegistrar(): ComponentRegistrarInterface
    {
        if ($this->componentRegistrar === null) {
            $this->componentRegistrar = ObjectManager::getInstance()->get(ComponentRegistrarInterface::class);
        }
        return $this->componentRegistrar;
    }

    /**
     * Lazily get file driver.
     *
     * @return FileDriver
     */
    private function getFileDriver(): FileDriver
    {
        if ($this->fileDriver === null) {
            $this->fileDriver = ObjectManager::getInstance()->get(FileDriver::class);
        }
        return $this->fileDriver;
    }

    /**
     * Lazily get JSON serializer.
     *
     * @return Json
     */
    private function getJsonSerializer(): Json
    {
        if ($this->jsonSerializer === null) {
            $this->jsonSerializer = ObjectManager::getInstance()->get(Json::class);
        }
        return $this->jsonSerializer;
    }

    /**
     * Get local module version for Jscriptz_Subcats from composer.json.
     *
     * @return string|null
     */
    private function getLocalVersion(): ?string
    {
        try {
            $modulePath = $this->getComponentRegistrar()->getPath(
                ComponentRegistrarInterface::MODULE,
                'Jscriptz_Subcats'
            );

            if ($modulePath) {
                $composerFile = $modulePath . '/composer.json';
                $fileDriver = $this->getFileDriver();
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                if ($fileDriver->isExists($composerFile)) {
                    $content = $fileDriver->fileGetContents($composerFile);
                    $data = $this->getJsonSerializer()->unserialize($content);
                    if (is_array($data) && !empty($data['version'])) {
                        return (string)$data['version'];
                    }
                }
            }
        } catch (\Throwable $e) {
            // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
            unset($e); // Fall through to module list fallback
        }

        // Fallback to module list (setup_module table)
        try {
            $info = $this->getModuleList()->getOne('Jscriptz_Subcats');
            if (is_array($info) && isset($info['setup_version'])) {
                return (string)$info['setup_version'];
            }
        } catch (\Throwable $e) {
            // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
            unset($e); // Ignore, version info is optional
        }

        return null;
    }

    /**
     * Ensure trial_start is set and return it.
     *
     * @return \DateTimeImmutable
     */
    private function ensureTrialStart(): \DateTimeImmutable
    {
        $configPath = 'jscriptz_subcats/license/trial_start';

        $raw = (string)$this->_config->getValue(
            $configPath,
            $this->getScope(),
            $this->getScopeId()
        );

        try {
            if ($raw) {
                return new \DateTimeImmutable($raw, new \DateTimeZone('UTC'));
            }
        } catch (\Throwable $e) {
            // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
            unset($e); // Reset below
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $this->getConfigWriter()->save(
            $configPath,
            $now->format('Y-m-d'),
            $this->getScope(),
            $this->getScopeId()
        );

        return $now;
    }

    /**
     * Get remaining trial days (0..TRIAL_DAYS).
     *
     * @return int
     */
    private function getTrialDaysRemaining(): int
    {
        $start = $this->ensureTrialStart();
        $now   = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $daysUsed = (int)$now->diff($start)->format('%a');

        return max(0, self::TRIAL_DAYS - $daysUsed);
    }

    /**
     * Store a human-readable license status.
     *
     * @param string $status
     * @param string $message
     * @return void
     */
    private function setLicenseStatus(string $status, $message): void
    {
        $configPath = 'jscriptz_subcats/license/license_status';
        $value      = (string)$message;

        $this->getConfigWriter()->save(
            $configPath,
            $value,
            $this->getScope(),
            $this->getScopeId()
        );
    }

    /**
     * Persist version + news messages to config.
     *
     * @param string|null $localVersion
     * @param array $updateInfo
     * @return void
     */
    private function saveVersionAndNews(?string $localVersion, array $updateInfo): void
    {
        $writer = $this->getConfigWriter();

        if ($localVersion) {
            $updateAvailable = !empty($updateInfo['updateAvailable']);

            if ($updateAvailable) {
                // Example: "0.0.7 Updates available" + link to My Licenses
                $versionMsg = (string)__(
                    '%1 Updates available. <a href="%2" target="_blank">View My Licenses</a>',
                    $localVersion,
                    self::LICENSE_ACCOUNT_URL
                );
            } else {
                // Example: "0.0.8 (newest version)"
                $versionMsg = (string)__(
                    '%1 (newest version)',
                    $localVersion
                );
            }

            $writer->save(
                'jscriptz_subcats/license/version_status',
                $versionMsg,
                $this->getScope(),
                $this->getScopeId()
            );
        }

        if (!empty($updateInfo['newsMessage'])) {
            $writer->save(
                'jscriptz_subcats/license/news_message',
                (string)$updateInfo['newsMessage'],
                $this->getScope(),
                $this->getScopeId()
            );
        }
    }

    /**
     * Call mage.jscriptz.com to check for updates + news.
     *
     * @param string|null $localVersion
     * @return array
     */
    private function checkForUpdates(?string $localVersion): array
    {
        $result = [
            'latestVersion'   => null,
            'updateAvailable' => false,
            'message'         => null,
            'newsMessage'     => null,
        ];

        if ($localVersion === null) {
            return $result;
        }

        try {
            $payload = [
                'moduleCode'     => self::MODULE_CODE,
                'currentVersion' => $localVersion,
            ];

            $curl = $this->getCurl();
            $curl->setTimeout(5);
            $curl->addHeader('Content-Type', 'application/json');
            $curl->post(self::UPDATE_URL, json_encode($payload));

            if ($curl->getStatus() !== 200) {
                return $result;
            }

            $body = (string) $curl->getBody();
            try {
                $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                return $result;
            }
            if (!is_array($data)) {
                return $result;
            }
            if (!is_array($data)) {
                return $result;
            }

            $result['latestVersion']   = $data['latestVersion']   ?? null;
            $result['updateAvailable'] = !empty($data['updateAvailable']);
            $result['message']         = $data['message']         ?? null;
            $result['newsMessage']     = $data['newsMessage']     ?? null;

            return $result;
        } catch (\Throwable $e) {
            return $result;
        }
    }

    /**
     * Validate / verify license key, manage trial, and ping update API.
     *
     * @return $this
     * @throws LocalizedException
     */
    public function afterSave()
    {
        $licenseKey = trim((string)$this->getValue());

        // After saving the license key, delegate all license/trial logic to the
        // central Jscriptz_License module via the ApiClient. This will:
        //  - Ensure a free trial row exists when there is no license key.
        //  - Persist License Status, Version Status, and News to config.
        //  - Verify real license keys (when present).
        $scopeType = $this->getScope();
        $scopeId   = (int)$this->getScopeId();

        $apiClient = $this->getApiClient();

        // Always sync update info (handles trial + version + news).
        $apiClient->syncUpdateInfo($scopeType, $scopeId);

        // Only verify when a real license key is configured.
        if ($licenseKey !== '') {
            $apiClient->syncVerifyInfo($scopeType, $scopeId);
        }

        return parent::afterSave();
    }
}
