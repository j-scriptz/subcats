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

namespace Jscriptz\Subcats\Model\License;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
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
 */
class ApiClient
{
    // ...
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * @var UserFactory
     */
    private $userFactory;
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
    private const CONFIG_PATH_TRIAL_DAYS_REMAINING = 'jscriptz_subcats/license/trial_days_remaining';

    // Your two APIs (relative to base URL)
    private const API_UPDATE_URI = '/V1/jscriptz/license/update';
    private const API_VERIFY_URI = '/V1/jscriptz/license/verify';

    private WriterInterface $configWriter;
    private Curl $curl;
    private Json $json;
    private LoggerInterface $logger;
    private ModuleListInterface $moduleList;

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param RemoteAddress $remoteAddress
     * @param UserFactory $userFactory
     * @param WriterInterface $configWriter
     * @param Curl $curl
     * @param Json $json
     * @param LoggerInterface $logger
     * @param ModuleListInterface $moduleList
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
        ModuleListInterface $moduleList
    ) {
        $this->scopeConfig  = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->curl         = $curl;
        $this->json         = $json;
        $this->logger       = $logger;
        $this->moduleList = $moduleList;
        $this->storeManager  = $storeManager;
        $this->remoteAddress = $remoteAddress;
        $this->userFactory   = $userFactory;
    }

    /**
     * Hit /V1/jscriptz/license/update and store latestVersion/newsMessage/etc.
     */
    public function syncUpdateInfo(
        string $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        int $scopeId = 0
    ): void {
        $licenseKey = (string)$this->scopeConfig->getValue(self::XML_PATH_LICENSE_KEY);

        // Always sync update info (even without a license key) so trial users still see:
        // - News & Updates
        // - Version status / update available
        $domain = rtrim(
            (string)$this->scopeConfig->getValue('web/unsecure/base_url', ScopeInterface::SCOPE_STORE),
            '/'
        );

        $installedVersion = $this->getInstalledVersion();
        $endpoint = self::UPDATE_URL;

        try {

            $payloadArray = [
                'licenseKey'     => $licenseKey,        // currently ignored by server update(), kept for forward-compat
                'domain'         => $domain,            // currently ignored by server update()
                'moduleCode'     => self::MODULE_CODE,  // MUST match License server expectations ("jscriptz_subcats")
                'currentVersion' => $installedVersion,  // MUST be provided so server can compare and set updateAvailable
            ];

            $payloadArray = array_merge(
                $payloadArray,
                $this->getLicenseMetadata(),
                $this->getEnvironmentMetadata()
            );

            $payload = $this->json->serialize($payloadArray);

            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->post($endpoint, $payload);

            $status = (int)$this->curl->getStatus();
            $body   = (string)$this->curl->getBody();

            $this->logger->info('Jscriptz_Subcats: update API response', ['body' => $body]);

            if ($status !== 200) {
                $this->configWriter->save(
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
                $decoded = $this->json->unserialize($body);
            } catch (\Throwable $e) {
                $this->logger->warning(
                    'Jscriptz_Subcats: update API response not valid JSON: ' . $e->getMessage()
                );
            }

            // License server currently returns a JSON LIST (not object) because it returns a plain `array` via webapi:
            //   [ latestVersion, updateAvailable, message, newsMessage, trialDaysRemaining, trialExpired, licenseStatus, trialStatus, trialMessage ]
            // But we support both list and object forms for durability.
            $latestVersion = '';
            $newsMessage   = '';
            $serverMessage = '';
            $updateAvailable = null;

            if (is_array($decoded)) {
                $isList = array_values($decoded) === $decoded;

                if ($isList) {
                    $latestVersion    = isset($decoded[0]) ? (string)$decoded[0] : '';
                    $updateAvailable  = $decoded[1] ?? null;
                    $serverMessage    = isset($decoded[2]) ? (string)$decoded[2] : '';
                    $newsMessage      = isset($decoded[3]) ? (string)$decoded[3] : '';
                } else {
                    $latestVersion    = !empty($decoded['latestVersion']) ? (string)$decoded['latestVersion'] : '';
                    $updateAvailable  = $decoded['updateAvailable'] ?? null;
                    $serverMessage    = !empty($decoded['message']) ? (string)$decoded['message'] : '';
                    $newsMessage      = !empty($decoded['newsMessage']) ? (string)$decoded['newsMessage'] : '';
                }
            }

            $latestVersion = trim($latestVersion);

            // Build Version Status exactly how you want it:
            // - If latest == installed => "<installed> (Latest Version)"
            // - If latest > installed  => "Installed: <installed> — Newer version available (<latest>) Download Here"
            $download = 'Update <a href="https://github.com/j-scriptz/subcats" target="_blank">Instructions</a>';

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

            $this->configWriter->save(
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
                    $this->configWriter->save(
                        self::CONFIG_PATH_LICENSE_STATUS,
                        $licenseStatusLabel,
                        $scopeType,
                        $scopeId
                    );
                }

                // Persist machine-readable trial flags for frontend gating
                if ($trialExpired !== null) {
                    $this->configWriter->save(
                        self::CONFIG_PATH_TRIAL_EXPIRED,
                        $trialExpired ? '1' : '0',
                        $scopeType,
                        $scopeId
                    );
                }
                if ($trialDaysRemaining !== null) {
                    $this->configWriter->save(
                        self::CONFIG_PATH_TRIAL_DAYS_REMAINING,
                        (string)$trialDaysRemaining,
                        $scopeType,
                        $scopeId
                    );
                }
            }

            if (trim($newsMessage) !== '') {
                $this->configWriter->save(
                    self::CONFIG_PATH_NEWS_MESSAGE,
                    $newsMessage,
                    $scopeType,
                    $scopeId
                );
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                'Jscriptz_Subcats: update API sync exception: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }

    /**
     * Hit /V1/jscriptz/license/verify and store license_status/verify_message
     */
    public function syncVerifyInfo(
        string $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        int $scopeId = 0
    ): void {
        $licenseKey = (string)$this->scopeConfig->getValue(self::XML_PATH_LICENSE_KEY);

        $domain = rtrim(
            (string)$this->scopeConfig->getValue('web/unsecure/base_url', ScopeInterface::SCOPE_STORE),
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
                $this->getLicenseMetadata(),
                $this->getEnvironmentMetadata()
            );

            $payload = $this->json->serialize($payloadArray);

            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->post($endpoint, $payload);

            $status = (int)$this->curl->getStatus();
            $body   = (string)$this->curl->getBody();

            $this->logger->info('Jscriptz_Subcats: verify API response', ['body' => $body]);

            if ($status !== 200) {
                $this->configWriter->save(
                    self::CONFIG_PATH_VERIFY_MESSAGE,
                    (string)__('License verification failed (HTTP %1).', $status),
                    $scopeType,
                    $scopeId
                );
                return;
            }

            $decoded = null;
            try {
                $decoded = $this->json->unserialize($body);
            } catch (\Throwable $e) {
                $this->logger->warning(
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
            $normalized = $this->normalizeLicenseStatusMessage($verifyMessage, $licenseKey);

            $this->configWriter->save(
                self::CONFIG_PATH_VERIFY_MESSAGE,
                $normalized,
                $scopeType,
                $scopeId
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                'Jscriptz_Subcats: verify API sync exception: ' . $e->getMessage(),
                ['exception' => $e]
            );

            $this->configWriter->save(
                self::CONFIG_PATH_VERIFY_MESSAGE,
                (string)__('License verification error: %1', $e->getMessage()),
                $scopeType,
                $scopeId
            );
        }
    }

    private function getEnvironmentMetadata(): array
    {
        $store = $this->storeManager->getStore();

        $baseUrl       = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK, false);
        $baseUrlSecure = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK, true);

        $generalEmail = $this->scopeConfig->getValue(
            'trans_email/ident_general/email',
            ScopeInterface::SCOPE_STORE
        );
        $salesEmail = $this->scopeConfig->getValue(
            'trans_email/ident_sales/email',
            ScopeInterface::SCOPE_STORE
        );
        $supportEmail = $this->scopeConfig->getValue(
            'trans_email/ident_support/email',
            ScopeInterface::SCOPE_STORE
        );

        $storeName = $this->scopeConfig->getValue(
            'general/store_information/name',
            ScopeInterface::SCOPE_STORE
        );
        $streetLine1 = $this->scopeConfig->getValue(
            'general/store_information/street_line1',
            ScopeInterface::SCOPE_STORE
        );
        $streetLine2 = $this->scopeConfig->getValue(
            'general/store_information/street_line2',
            ScopeInterface::SCOPE_STORE
        );
        $city = $this->scopeConfig->getValue(
            'general/store_information/city',
            ScopeInterface::SCOPE_STORE
        );
        $region = $this->scopeConfig->getValue(
            'general/store_information/region',
            ScopeInterface::SCOPE_STORE
        );
        $postcode = $this->scopeConfig->getValue(
            'general/store_information/postcode',
            ScopeInterface::SCOPE_STORE
        );
        $countryId = $this->scopeConfig->getValue(
            'general/store_information/country_id',
            ScopeInterface::SCOPE_STORE
        );
        $telephone = $this->scopeConfig->getValue(
            'general/store_information/phone',
            ScopeInterface::SCOPE_STORE
        );

        $addressParts = array_filter([
            $storeName,
            $streetLine1,
            $streetLine2,
            $city,
            $region,
            $postcode,
            $countryId
        ]);
        $storeAddress = implode(', ', $addressParts);

        // Simple: use admin user with ID 1 (your main admin)
        $adminUser = $this->userFactory->create()->load(1);
        $adminEmail = $adminUser->getEmail() ?: null;
        $adminFirstname = $adminUser->getFirstname() ?: null;
        $adminLastname = $adminUser->getLastname() ?: null;

        $clientIp = $this->remoteAddress->getRemoteAddress();

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

    private function getLicenseMetadata(): array
    {
        $licenseStatus = $this->scopeConfig->getValue(
            'jscriptz_subcats/license/status',
            ScopeInterface::SCOPE_STORE
        );
        $trialStart = $this->scopeConfig->getValue(
            'jscriptz_subcats/license/trial_start',
            ScopeInterface::SCOPE_STORE
        );

        return [
            'license_status' => $licenseStatus,
            'trial_start'    => $trialStart,
        ];
    }

    /**
     * Normalize the human-facing license status message so we keep a friendly
     * Free Trial message instead of raw "not found" errors while a trial is active.
     */
    private function normalizeLicenseStatusMessage(string $message, string $licenseKey): string
    {
        $trimmed = trim($message);
        $lower   = strtolower($trimmed);

        // If server already returns a trial/active message, just use it.
        if ($trimmed !== '' && (strpos($lower, 'trial') !== false || strpos($lower, 'active') !== false)) {
            return $trimmed;
        }

        // If there is no license key yet, fall back to local 30‑day trial info
        if ($licenseKey === '' && ($trimmed === '' || strpos($lower, 'not found') !== false)) {
            $daysRemaining = $this->getTrialDaysRemaining();

            if ($daysRemaining > 0) {
                return (string)__('Free Trial (%1 days remaining)', $daysRemaining);
            }

            if ($daysRemaining === 0 && $this->hasTrialStart()) {
                return (string)__('Free Trial expired.');
            }
        }

        // Last resort default
        return $trimmed !== '' ? $trimmed : (string)__('Free Trial (30 Days Remaining)');
    }

    /**
     * Calculate remaining trial days based on jscriptz_subcats/license/trial_start.
     */
    private function getTrialDaysRemaining(): int
    {
        $trialStart = (string)$this->scopeConfig->getValue(
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
            $this->logger->warning(
                'Jscriptz_Subcats: invalid trial_start value in config.',
                ['exception' => $e]
            );
            return 0;
        }
    }

    /**
     * Check if a trial_start date exists.
     */

    private function getInstalledVersion(): string
    {
        try {
            $info = $this->moduleList->getOne(self::MODULE_NAME);
            if (is_array($info) && !empty($info['setup_version'])) {
                return (string)$info['setup_version'];
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return '';
    }

    private function hasTrialStart(): bool
    {
        $trialStart = (string)$this->scopeConfig->getValue(
            'jscriptz_subcats/license/trial_start',
            ScopeInterface::SCOPE_STORE
        );

        return $trialStart !== '';
    }

    /**
     * Very simple base URL helper: current Magento base URL.
     * If the APIs live on a different host, change this to read a config,
     * or just hard-code https://mage.jscriptz.com.
     */
    private function getBaseUrl(): string
    {
        // If the License APIs live on the SAME Magento instance, this works:
        // http[s]://mage.jscriptz.com + /V1/jscriptz/license/...
        return rtrim($this->scopeConfig->getValue('web/unsecure/base_url'), '/');
        // or hard-code: return 'https://mage.jscriptz.com';
    }
}
