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

namespace Jscriptz\Subcats\Model\License;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\User\Model\UserFactory;
use Psr\Log\LoggerInterface;

/**
 * Model ApiClient
 *
 * @license  https://mage.jscriptz.com/LICENSE.txt Proprietary
 * @link     https://mage.jscriptz.com
 */
class ApiClient
{
    // ...
    /**
     * Store manager instance
     *
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * Scope config instance
     *
     * @var ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * Remote address instance
     *
     * @var RemoteAddress
     */
    private $_remoteAddress;

    /**
     * User factory instance
     *
     * @var UserFactory
     */
    private $_userFactory;
    private const MODULE_CODE = 'jscriptz_subcats';
    private const MODULE_NAME = 'Jscriptz_Subcats';
    private const VERIFY_URL  = 'https://mage.jscriptz.com/rest/V1/jscriptz/license/verify';
    private const UPDATE_URL = 'https://mage.jscriptz.com/rest/V1/jscriptz/license/update';
    // Adjust this to whatever config_path your Subcats license key actually uses
    private const XML_PATH_LICENSE_KEY = 'jscriptz_subcats/license/license_key';

    // These are the shared config paths you already use
    private const CONFIG_PATH_NEWS_MESSAGE   = 'jscriptz_subcats/license/news_message';
    private const CONFIG_PATH_VERSION_STATUS = 'jscriptz_subcats/license/version_status';
    private const CONFIG_PATH_VERIFY_MESSAGE = 'jscriptz_subcats/license/verify_message';
    private const CONFIG_PATH_LICENSE_STATUS = 'jscriptz_subcats/license/license_status';
    private const CONFIG_PATH_TRIAL_EXPIRED = 'jscriptz_subcats/license/trial_expired';
    private const CONFIG_PATH_TRIAL_DAYS_REMAINING
        = 'jscriptz_subcats/license/trial_days_remaining';

    // Your two APIs (relative to base URL)
    private const API_UPDATE_URI = '/V1/jscriptz/license/update';
    private const API_VERIFY_URI = '/V1/jscriptz/license/verify';

    /**
     * Config writer instance
     *
     * @var WriterInterface
     */
    private WriterInterface $_configWriter;

    /**
     * Curl client instance
     *
     * @var Curl
     */
    private Curl $_curl;

    /**
     * JSON serializer instance
     *
     * @var Json
     */
    private Json $_json;

    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    private LoggerInterface $_logger;

    /**
     * Module list instance
     *
     * @var ModuleListInterface
     */
    private ModuleListInterface $_moduleList;

    /**
     * Component registrar instance
     *
     * @var ComponentRegistrarInterface
     */
    private ComponentRegistrarInterface $_componentRegistrar;

    /**
     * File driver instance
     *
     * @var FileDriver
     */
    private FileDriver $_fileDriver;

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface        $scopeConfig        Scope config interface
     * @param StoreManagerInterface       $storeManager       Store manager interface
     * @param RemoteAddress               $remoteAddress      Remote address instance
     * @param UserFactory                 $userFactory        User factory instance
     * @param WriterInterface             $configWriter       Config writer interface
     * @param Curl                        $curl               HTTP client instance
     * @param Json                        $json               JSON serializer instance
     * @param LoggerInterface             $logger             Logger interface
     * @param ModuleListInterface         $moduleList         Module list interface
     * @param ComponentRegistrarInterface $componentRegistrar Component registrar
     * @param FileDriver                  $fileDriver         File driver instance
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        RemoteAddress $remoteAddress,
        UserFactory $userFactory,
        WriterInterface $configWriter,
        Curl $curl,
        Json $json,
        LoggerInterface $logger,
        ModuleListInterface $moduleList,
        ComponentRegistrarInterface $componentRegistrar,
        FileDriver $fileDriver
    ) {
        $this->_scopeConfig  = $scopeConfig;
        $this->_configWriter = $configWriter;
        $this->_curl         = $curl;
        $this->_json         = $json;
        $this->_logger       = $logger;
        $this->_moduleList = $moduleList;
        $this->_componentRegistrar = $componentRegistrar;
        $this->_fileDriver = $fileDriver;
        $this->_storeManager  = $storeManager;
        $this->_remoteAddress = $remoteAddress;
        $this->_userFactory   = $userFactory;
    }

    /**
     * Hit /V1/jscriptz/license/update and store latestVersion/newsMessage/etc.
     *
     * @param string $scopeType Scope type for config save
     * @param int    $scopeId   Scope ID for config save
     *
     * @return void
     */
    public function syncUpdateInfo(
        string $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        int $scopeId = 0
    ): void {
        $licenseKey = (string)$this->_scopeConfig->getValue(self::XML_PATH_LICENSE_KEY);

        // Always sync update info (even without a license key) so trial users still see:
        // - News & Updates
        // - Version status / update available
        $domain = rtrim(
            (string)$this->_scopeConfig->getValue(
                'web/unsecure/base_url',
                ScopeInterface::SCOPE_STORE
            ),
            '/'
        );

        $installedVersion = $this->_getInstalledVersion();
        $endpoint = self::UPDATE_URL;

        try {

            $payloadArray = [
                'licenseKey'     => $licenseKey,
                'domain'         => $domain,
                'moduleCode'     => self::MODULE_CODE,
                'currentVersion' => $installedVersion,
            ];

            $payloadArray = array_merge(
                $payloadArray,
                $this->_getLicenseMetadata(),
                $this->_getEnvironmentMetadata()
            );

            $payload = $this->_json->serialize($payloadArray);

            $this->_curl->addHeader('Content-Type', 'application/json');
            $this->_curl->post($endpoint, $payload);

            $status = (int)$this->_curl->getStatus();
            $body   = (string)$this->_curl->getBody();

            $this->_logger->info(
                'Jscriptz_Subcats: update API response',
                ['body' => $body]
            );

            if ($status !== 200) {
                $this->_configWriter->save(
                    self::CONFIG_PATH_VERSION_STATUS,
                    (string)__('Update check failed (HTTP %1).', $status),
                    $scopeType,
                    $scopeId
                );
                // Don't overwrite news on transient errors; keep last known value.
                return;
            }

            $decoded = null;
            try {
                $decoded = $this->_json->unserialize($body);
            } catch (\Throwable $e) {
                $this->_logger->warning(
                    'Jscriptz_Subcats: update API response not valid JSON: '
                    . $e->getMessage()
                );
            }

            // License server currently returns a JSON LIST (not object)
            // Format: [ latestVersion, updateAvailable, message, newsMessage,
            // trialDaysRemaining, trialExpired, licenseStatus, trialStatus,
            // trialMessage ]
            // But we support both list and object forms for durability.
            $latestVersion = '';
            $newsMessage   = '';
            $serverMessage = '';
            $updateAvailable = null;

            if (is_array($decoded)) {
                $isList = array_values($decoded) === $decoded;

                if ($isList) {
                    $latestVersion = isset($decoded[0])
                        ? (string)$decoded[0]
                        : '';
                    $updateAvailable  = $decoded[1] ?? null;
                    $serverMessage = isset($decoded[2])
                        ? (string)$decoded[2]
                        : '';
                    $newsMessage = isset($decoded[3])
                        ? (string)$decoded[3]
                        : '';
                } else {
                    $latestVersion = !empty($decoded['latestVersion'])
                        ? (string)$decoded['latestVersion']
                        : '';
                    $updateAvailable  = $decoded['updateAvailable'] ?? null;
                    $serverMessage = !empty($decoded['message'])
                        ? (string)$decoded['message']
                        : '';
                    $newsMessage = !empty($decoded['newsMessage'])
                        ? (string)$decoded['newsMessage']
                        : '';
                }
            }

            $latestVersion = trim($latestVersion);

            // Build Version Status exactly how you want it:
            // - If latest == installed => "<installed> (Latest Version)"
            // - If latest > installed => "Installed: <installed> — Newer
            // version available (<latest>) Download Here"
            // phpcs:ignore Magento2.SQL.RawQuery.FoundRawSql
            $download = 'Update <a href="https://github.com/j-scriptz/subcats"'
                . ' target="_blank">Instructions</a>';

            if ($installedVersion !== '' && $latestVersion !== '') {
                if (version_compare($installedVersion, $latestVersion, '<')) {
                    $versionStatus = sprintf(
                        '%s (Installed) — Newer version available (%s) %s',
                        $installedVersion,
                        $latestVersion,
                        $download
                    );
                } else {
                    $versionStatus = sprintf('%s (Latest Version)', $installedVersion);
                }
            } elseif ($installedVersion !== '' && $latestVersion === '') {
                // If server didn't send latest, show installed and optionally server message
                $versionStatus = $serverMessage !== '' ? $serverMessage : sprintf('Installed: %s', $installedVersion);
            } else {
                // Worst-case fallback
                $versionStatus = $serverMessage !== '' ? $serverMessage : (string)__('No update information.');
            }

            $this->_configWriter->save(
                self::CONFIG_PATH_VERSION_STATUS,
                $versionStatus,
                $scopeType,
                $scopeId
            );

            // If the license server sent license/trial info, persist a friendly License Status.
            if (is_array($decoded)) {
                $licenseStatusFromServer = '';
                $trialMessageFromServer  = '';
                $trialDaysRemaining      = null;
                $trialExpired            = null;

                $isList = array_values($decoded) === $decoded;

                if (!$isList) {
                    // Associative array format
                    $licenseStatusFromServer = isset($decoded['licenseStatus'])
                        ? (string)$decoded['licenseStatus']
                        : '';
                    $trialMessageFromServer = isset($decoded['trialMessage'])
                        ? (string)$decoded['trialMessage']
                        : '';
                    $trialDaysRemaining = isset($decoded['trialDaysRemaining'])
                        ? (int)$decoded['trialDaysRemaining']
                        : null;
                    $trialExpired = isset($decoded['trialExpired'])
                        ? (bool)$decoded['trialExpired']
                        : null;
                } else {
                    // ✅ FIXED: Read from indexed array format
                    // Response format: [latestVersion, updateAvailable, message, newsMessage,
                    //                   trialDaysRemaining, trialExpired, licenseStatus, trialStatus, trialMessage]
                    $trialDaysRemaining = isset($decoded[4]) && $decoded[4] !== null
                        ? (int)$decoded[4]
                        : null;
                    $trialExpired = isset($decoded[5]) && $decoded[5] !== null
                        ? (bool)$decoded[5]
                        : null;
                    $licenseStatusFromServer = isset($decoded[6]) && $decoded[6] !== null
                        ? (string)$decoded[6]
                        : '';
                    // Skip index 7 (trialStatus object) - we don't need it for display
                    $trialMessageFromServer = isset($decoded[8]) && $decoded[8] !== null
                        ? (string)$decoded[8]
                        : '';
                }

                $licenseStatusLabel = '';

                if ($trialMessageFromServer !== '') {
                    $licenseStatusLabel = $trialMessageFromServer;
                } elseif ($licenseStatusFromServer !== '') {
                    if (strtolower($licenseStatusFromServer) === 'trial') {
                        if ($trialDaysRemaining !== null) {
                            $licenseStatusLabel = (string)__(
                                'Free Trial (%1 days remaining)',
                                $trialDaysRemaining
                            );
                        } else {
                            $licenseStatusLabel = (string)__('Free Trial');
                        }
                    } elseif (strtolower($licenseStatusFromServer) === 'expired_trial') {
                        $licenseStatusLabel = (string)__('Free Trial has expired.');
                    } else {
                        // Generic status passthrough
                        $licenseStatusLabel = $licenseStatusFromServer;
                    }
                }

                // Persist human-readable License Status (for admin UI)
                if ($licenseStatusLabel !== '') {
                    $this->_configWriter->save(
                        self::CONFIG_PATH_LICENSE_STATUS,
                        $licenseStatusLabel,
                        $scopeType,
                        $scopeId
                    );
                }

                // Persist machine-readable trial flags for frontend gating
                if ($trialExpired !== null) {
                    $this->_configWriter->save(
                        self::CONFIG_PATH_TRIAL_EXPIRED,
                        $trialExpired ? '1' : '0',
                        $scopeType,
                        $scopeId
                    );
                }
                if ($trialDaysRemaining !== null) {
                    $this->_configWriter->save(
                        self::CONFIG_PATH_TRIAL_DAYS_REMAINING,
                        (string)$trialDaysRemaining,
                        $scopeType,
                        $scopeId
                    );
                }
            }

            if (trim($newsMessage) !== '') {
                $this->_configWriter->save(
                    self::CONFIG_PATH_NEWS_MESSAGE,
                    $newsMessage,
                    $scopeType,
                    $scopeId
                );
            }
        } catch (\Throwable $e) {
            $this->_logger->error(
                'Jscriptz_Subcats: update API sync exception: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }

    /**
     * Hit /V1/jscriptz/license/verify and store license_status/verify_message
     *
     * @param string $scopeType Scope type for config save
     * @param int    $scopeId   Scope ID for config save
     *
     * @return void
     */
    public function syncVerifyInfo(
        string $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        int $scopeId = 0
    ): void {
        $licenseKey = (string)$this->_scopeConfig->getValue(self::XML_PATH_LICENSE_KEY);

        $domain = rtrim(
            (string)$this->_scopeConfig->getValue('web/unsecure/base_url', ScopeInterface::SCOPE_STORE),
            '/'
        );

        $endpoint = self::VERIFY_URL;

        try {
            $payloadArray = [
                'licenseKey' => $licenseKey,
                'moduleCode' => self::MODULE_CODE,
                'domain'     => $domain,
            ];

            $payloadArray = array_merge(
                $payloadArray,
                $this->_getLicenseMetadata(),
                $this->_getEnvironmentMetadata()
            );

            $payload = $this->_json->serialize($payloadArray);

            $this->_curl->addHeader('Content-Type', 'application/json');
            $this->_curl->post($endpoint, $payload);

            $status = (int)$this->_curl->getStatus();
            $body   = (string)$this->_curl->getBody();

            $this->_logger->info('Jscriptz_Subcats: verify API response', ['body' => $body]);

            if ($status !== 200) {
                $this->_configWriter->save(
                    self::CONFIG_PATH_VERIFY_MESSAGE,
                    (string)__('License verification failed (HTTP %1).', $status),
                    $scopeType,
                    $scopeId
                );
                return;
            }

            $decoded = null;
            try {
                $decoded = $this->_json->unserialize($body);
            } catch (\Throwable $e) {
                $this->_logger->warning(
                    'Jscriptz_Subcats: verify API response not valid JSON: ' . $e->getMessage()
                );
            }

            $licenseStatus = '';
            $verifyMessage = '';

            if (is_array($decoded)) {
                $isList = array_values($decoded) === $decoded;

                if ($isList) {
                    $licenseStatus = isset($decoded[0]) ? (string)$decoded[0] : '';
                    $verifyMessage = isset($decoded[1]) ? (string)$decoded[1] : '';
                } else {
                    $licenseStatus = !empty($decoded['status']) ? (string)$decoded['status'] : '';
                    $verifyMessage = !empty($decoded['message']) ? (string)$decoded['message'] : '';
                }
            }

            // Normalize the verify message to be user-friendly for free trials
            $normalized = $this->_normalizeLicenseStatusMessage($verifyMessage, $licenseKey);

            $this->_configWriter->save(
                self::CONFIG_PATH_VERIFY_MESSAGE,
                $normalized,
                $scopeType,
                $scopeId
            );
        } catch (\Throwable $e) {
            $this->_logger->error(
                'Jscriptz_Subcats: verify API sync exception: ' . $e->getMessage(),
                ['exception' => $e]
            );

            $this->_configWriter->save(
                self::CONFIG_PATH_VERIFY_MESSAGE,
                (string)__('License verification error: %1', $e->getMessage()),
                $scopeType,
                $scopeId
            );
        }
    }

    /**
     * Get environment metadata.
     *
     * @return array
     */
    private function _getEnvironmentMetadata(): array
    {
        $store = $this->_storeManager->getStore();

        $baseUrl       = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK, false);
        $baseUrlSecure = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK, true);

        $generalEmail = $this->_scopeConfig->getValue(
            'trans_email/ident_general/email',
            ScopeInterface::SCOPE_STORE
        );
        $salesEmail = $this->_scopeConfig->getValue(
            'trans_email/ident_sales/email',
            ScopeInterface::SCOPE_STORE
        );
        $supportEmail = $this->_scopeConfig->getValue(
            'trans_email/ident_support/email',
            ScopeInterface::SCOPE_STORE
        );

        $storeName = $this->_scopeConfig->getValue(
            'general/store_information/name',
            ScopeInterface::SCOPE_STORE
        );
        $streetLine1 = $this->_scopeConfig->getValue(
            'general/store_information/street_line1',
            ScopeInterface::SCOPE_STORE
        );
        $streetLine2 = $this->_scopeConfig->getValue(
            'general/store_information/street_line2',
            ScopeInterface::SCOPE_STORE
        );
        $city = $this->_scopeConfig->getValue(
            'general/store_information/city',
            ScopeInterface::SCOPE_STORE
        );
        $region = $this->_scopeConfig->getValue(
            'general/store_information/region',
            ScopeInterface::SCOPE_STORE
        );
        $postcode = $this->_scopeConfig->getValue(
            'general/store_information/postcode',
            ScopeInterface::SCOPE_STORE
        );
        $countryId = $this->_scopeConfig->getValue(
            'general/store_information/country_id',
            ScopeInterface::SCOPE_STORE
        );
        $telephone = $this->_scopeConfig->getValue(
            'general/store_information/phone',
            ScopeInterface::SCOPE_STORE
        );

        $addressParts = array_filter(
            [
            $storeName,
            $streetLine1,
            $streetLine2,
            $city,
            $region,
            $postcode,
            $countryId
            ]
        );
        $storeAddress = implode(', ', $addressParts);

        // Simple: use admin user with ID 1 (your main admin)
        $adminUser = $this->_userFactory->create()->load(1);
        $adminEmail = $adminUser->getEmail() ?: null;
        $adminFirstname = $adminUser->getFirstname() ?: null;
        $adminLastname = $adminUser->getLastname() ?: null;

        $clientIp = $this->_remoteAddress->getRemoteAddress();

        return [
            'admin_email'          => $adminEmail,
            'admin_firstname'      => $adminFirstname,
            'admin_lastname'       => $adminLastname,
            'store_email_general'  => $generalEmail,
            'store_email_sales'    => $salesEmail,
            'store_email_support'  => $supportEmail,
            'base_url'             => $baseUrl,
            'base_url_secure'      => $baseUrlSecure,
            'store_address'        => $storeAddress,
            'store_telephone'      => $telephone,
            'client_ip'            => $clientIp,
        ];
    }

    /**
     * Get license metadata.
     *
     * @return array
     */
    private function _getLicenseMetadata(): array
    {
        $licenseStatus = $this->_scopeConfig->getValue(
            'jscriptz_subcats/license/status',
            ScopeInterface::SCOPE_STORE
        );
        $trialStart = $this->_scopeConfig->getValue(
            'jscriptz_subcats/license/trial_start',
            ScopeInterface::SCOPE_STORE
        );

        return [
            'license_status' => $licenseStatus,
            'trial_start'    => $trialStart,
        ];
    }

    /**
     * Normalize the human-facing license status message.
     *
     * Keep a friendly Free Trial message instead of raw "not found" errors.
     *
     * @param string $message    License status message from server
     * @param string $licenseKey Current license key
     *
     * @return string
     */
    private function _normalizeLicenseStatusMessage(string $message, string $licenseKey): string
    {
        $trimmed = trim($message);
        $lower   = strtolower($trimmed);

        // If server already returns a trial/active message, just use it.
        if ($trimmed !== '' && (strpos($lower, 'trial') !== false || strpos($lower, 'active') !== false)) {
            return $trimmed;
        }

        // If there is no license key yet, fall back to local 30‑day trial info
        if ($licenseKey === '' && ($trimmed === '' || strpos($lower, 'not found') !== false)) {
            $daysRemaining = $this->_getTrialDaysRemaining();

            if ($daysRemaining > 0) {
                return (string)__('Free Trial (%1 days remaining)', $daysRemaining);
            }

            if ($daysRemaining === 0 && $this->_hasTrialStart()) {
                return (string)__('Free Trial expired.');
            }
        }

        // Last resort default
        return $trimmed !== '' ? $trimmed : (string)__('Free Trial (30 Days Remaining)');
    }

    /**
     * Calculate remaining trial days based on trial_start config value.
     *
     * @return int
     */
    private function _getTrialDaysRemaining(): int
    {
        $trialStart = (string)$this->_scopeConfig->getValue(
            'jscriptz_subcats/license/trial_start',
            ScopeInterface::SCOPE_STORE
        );

        if ($trialStart === '') {
            return 0;
        }

        try {
            $start = new \DateTimeImmutable($trialStart, new \DateTimeZone('UTC'));
            $now   = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

            $daysUsed  = (int)$now->diff($start)->format('%a');
            $remaining = 30 - $daysUsed;

            return $remaining > 0 ? $remaining : 0;
        } catch (\Throwable $e) {
            $this->_logger->warning(
                'Jscriptz_Subcats: invalid trial_start value in config.',
                ['exception' => $e]
            );
            return 0;
        }
    }

    /**
     * Check if a trial_start date exists.
     *
     * @return bool
     */
    private function _hasTrialStart(): bool
    {
        $trialStart = (string)$this->_scopeConfig->getValue(
            'jscriptz_subcats/license/trial_start',
            ScopeInterface::SCOPE_STORE
        );

        return $trialStart !== '';
    }

    /**
     * Get installed version of the module from composer.json.
     *
     * @return string
     */
    private function _getInstalledVersion(): string
    {
        try {
            $modulePath = $this->_componentRegistrar->getPath(
                ComponentRegistrarInterface::MODULE,
                self::MODULE_NAME
            );

            if ($modulePath) {
                $composerFile = $modulePath . '/composer.json';
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                if ($this->_fileDriver->isExists($composerFile)) {
                    $content = $this->_fileDriver->fileGetContents($composerFile);
                    $data = $this->_json->unserialize($content);
                    if (is_array($data) && !empty($data['version'])) {
                        return (string)$data['version'];
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->_logger->warning(
                'Jscriptz_Subcats: Could not read composer.json version: ' . $e->getMessage()
            );
        }

        // Fallback to module list (setup_module table) if composer.json read fails
        try {
            $info = $this->_moduleList->getOne(self::MODULE_NAME);
            if (is_array($info) && !empty($info['setup_version'])) {
                return (string)$info['setup_version'];
            }
        } catch (\Throwable $e) {
            // Intentionally empty - fallback behavior
            unset($e);
        }

        return '';
    }

    /**
     * Get base URL helper.
     *
     * Very simple base URL helper: current Magento base URL.
     * If the APIs live on a different host, change this to read a config,
     * or just hard-code https://mage.jscriptz.com.
     *
     * @return string
     */
    private function _getBaseUrl(): string
    {
        // If the License APIs live on the SAME Magento instance, this works:
        // http[s]://mage.jscriptz.com + /V1/jscriptz/license/...
        return rtrim($this->_scopeConfig->getValue('web/unsecure/base_url'), '/');
        // or hard-code: return 'https://mage.jscriptz.com';
    }
}
