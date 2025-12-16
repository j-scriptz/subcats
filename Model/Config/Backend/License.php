<?php
declare(strict_types=1);

/**
 * Jscriptz LLC.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.
 * It is also available through the web at this URL:
 * https://mage.jscriptz.com/LICENSE.txt
 *
 ********************************************************************
 *
 * PHP Version 8+
 *
 * @category  Jscriptz
 * @package   Jscriptz_Subcats
 * @author    Jason Lotzer <jasonlotzer@gmail.com>
 * @copyright 2019 - 2025 Jscriptz LLC
 * @license   https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link      https://mage.jscriptz.com
 */

namespace Jscriptz\Subcats\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Jscriptz\Subcats\Model\License\ApiClient;
use JsonException;

/**
 * Backend model for validating and storing Jscriptz Subcats license key,
 * handling free trial countdown, version check, and server-driven news.
 *
 * @category Jscriptz
 * @package  Jscriptz_Subcats
 * @author   Jason Lotzer <jasonlotzer@gmail.com>
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class License extends Value
{
    public const VERIFY_URL
        = 'https://mage.jscriptz.com/rest/V1/jscriptz/license/verify';
    public const UPDATE_URL
        = 'https://mage.jscriptz.com/rest/V1/jscriptz/license/update';
    public const MODULE_CODE = 'jscriptz_subcats';
    public const TRIAL_DAYS = 30;
    public const LICENSE_ACCOUNT_URL
        = 'https://mage.jscriptz.com/jscriptz_license/account/';

    /**
     * Curl client.
     *
     * @var Curl
     */
    private Curl $_curl;

    /**
     * Store manager.
     *
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $_storeManager;

    /**
     * Config writer.
     *
     * @var WriterInterface
     */
    private WriterInterface $_configWriter;

    /**
     * Module list.
     *
     * @var ModuleListInterface
     */
    private ModuleListInterface $_moduleList;

    /**
     * API client.
     *
     * @var ApiClient
     */
    private ApiClient $_apiClient;

    /**
     * Component registrar.
     *
     * @var ComponentRegistrarInterface
     */
    private ComponentRegistrarInterface $_componentRegistrar;

    /**
     * File driver.
     *
     * @var FileDriver
     */
    private FileDriver $_fileDriver;

    /**
     * JSON serializer.
     *
     * @var Json
     */
    private Json $_jsonSerializer;

    /**
     * Constructor.
     *
     * @param Context                     $context             Context instance
     * @param Registry                    $registry            Registry instance
     * @param ScopeConfigInterface        $config              Scope config
     * @param TypeListInterface           $cacheTypeList       Cache type list
     * @param Curl                        $curl                Curl client
     * @param StoreManagerInterface       $storeManager        Store manager
     * @param WriterInterface             $configWriter        Config writer
     * @param ModuleListInterface         $moduleList          Module list
     * @param ApiClient                   $apiClient           API client
     * @param ComponentRegistrarInterface $componentRegistrar  Component registrar
     * @param FileDriver                  $fileDriver          File driver
     * @param Json                        $jsonSerializer      JSON serializer
     * @param AbstractResource|null       $resource            Resource model
     * @param AbstractDb|null             $resourceCollection  Resource collection
     * @param array                       $data                Additional data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Curl $curl,
        StoreManagerInterface $storeManager,
        WriterInterface $configWriter,
        ModuleListInterface $moduleList,
        ApiClient $apiClient,
        ComponentRegistrarInterface $componentRegistrar,
        FileDriver $fileDriver,
        Json $jsonSerializer,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_curl = $curl;
        $this->_storeManager = $storeManager;
        $this->_configWriter = $configWriter;
        $this->_moduleList = $moduleList;
        $this->_apiClient = $apiClient;
        $this->_componentRegistrar = $componentRegistrar;
        $this->_fileDriver = $fileDriver;
        $this->_jsonSerializer = $jsonSerializer;
    }

    /**
     * Get local module version for Jscriptz_Subcats from composer.json.
     *
     * @return string|null
     */
    private function _getLocalVersion(): ?string
    {
        try {
            $modulePath = $this->_componentRegistrar->getPath(
                ComponentRegistrarInterface::MODULE,
                'Jscriptz_Subcats'
            );

            if ($modulePath) {
                $composerFile = $modulePath . '/composer.json';
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                if ($this->_fileDriver->isExists($composerFile)) {
                    $content = $this->_fileDriver->fileGetContents($composerFile);
                    $data = $this->_jsonSerializer->unserialize($content);
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
            $info = $this->_moduleList->getOne('Jscriptz_Subcats');
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
    private function _ensureTrialStart(): \DateTimeImmutable
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

        $this->_configWriter->save(
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
    private function _getTrialDaysRemaining(): int
    {
        $start = $this->_ensureTrialStart();
        $now   = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $daysUsed = (int)$now->diff($start)->format('%a');

        return max(0, self::TRIAL_DAYS - $daysUsed);
    }

    /**
     * Call mage.jscriptz.com to check for updates + news.
     *
     * @param string|null $localVersion Local version of the module
     *
     * @return array
     */
    private function _checkForUpdates(?string $localVersion): array
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

            $this->_curl->setTimeout(5);
            $this->_curl->addHeader('Content-Type', 'application/json');
            $this->_curl->post(self::UPDATE_URL, json_encode($payload));

            if ($this->_curl->getStatus() !== 200) {
                return $result;
            }

            $body = (string) $this->_curl->getBody();
            try {
                $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
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
     *
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

        // Always sync update info (handles trial + version + news).
        $this->_apiClient->syncUpdateInfo($scopeType, $scopeId);

        // Only verify when a real license key is configured.
        if ($licenseKey !== '') {
            $this->_apiClient->syncVerifyInfo($scopeType, $scopeId);
        }

        return parent::afterSave();
    }
}
