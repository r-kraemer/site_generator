<?php

declare(strict_types=1);

/*
 *
 * This file is part of the "Site Generator" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 */

namespace Oktopuce\SiteGenerator\Wizard;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use Psr\Log\LogLevel;
use TYPO3\CMS\Backend\Exception\SiteValidationErrorException;
use Oktopuce\SiteGenerator\Domain\Repository\PagesRepository;
use Oktopuce\SiteGenerator\Dto\BaseDto;

/**
 * StateSiteConfiguration
 */
class StateSiteConfiguration extends StateBase implements SiteGeneratorStateInterface
{

    /**
     * Create site management
     *
     * @param SiteGeneratorWizard $context
     * @return void
     * @throws \Exception
     */
    public function process(SiteGeneratorWizard $context): void
    {
        // Create the domain name on first page
        $this->createSiteConfiguration($context->getSiteData());
    }

    /**
     * Create a site configuration
     *
     * @param BaseDto $siteData New site data
     * @throws \Exception
     * @return void
     */
    protected function createSiteConfiguration(BaseDto $siteData): void
    {
        if (!empty($siteData->getDomain())) {

            /** @var PagesRepository $pagesRepository */
            $pagesRepository = GeneralUtility::makeInstance(PagesRepository::class);
            $uids = $siteData->getMappingArrayMerge('pages');
            $rootSiteId = $pagesRepository->getRootSiteId($uids);

            if ($rootSiteId) {
                try {
                    // Get extension configuration
                    $extensionConfiguration = $this->getExtensionConfiguration();

                    $language = [];
                    $language['title'] = $extensionConfiguration['langTitle'];
                    $language['enabled'] = true;
                    $language['base'] = '/';
                    $language['typo3Language'] = 'default';
                    $language['locale'] = $extensionConfiguration['locale'];
                    $language['iso-639-1'] = $extensionConfiguration['iso-639-1'];
                    $language['websiteTitle'] = '';
                    $language['navigationTitle'] = $extensionConfiguration['navigationTitle'];
                    $language['hreflang'] = $extensionConfiguration['hreflang'];
                    $language['direction'] = $extensionConfiguration['direction'];
                    $language['flag'] = $extensionConfiguration['flag'];
                    $language['languageId'] = '0';

                    $newSiteConfiguration = [];
                    $newSiteConfiguration['rootPageId'] = $rootSiteId;
                    $newSiteConfiguration['websiteTitle'] = $siteData->getTitleSanitize();
                    $newSiteConfiguration['base'] = $siteData->getDomain();
                    $newSiteConfiguration['baseVariants'] = [];
                    $newSiteConfiguration['languages']['0'] = $language;

                    $siteIdentifier = $extensionConfiguration['siteIdentifierPrefix'] . $rootSiteId;

                    // Persist the configuration
                    /** @var SiteConfiguration $siteConfigurationManager */
                    $siteConfigurationManager = GeneralUtility::makeInstance(SiteConfiguration::class, Environment::getConfigPath() . '/sites');
                    $siteConfigurationManager->write($siteIdentifier, $newSiteConfiguration);

                    $this->log(LogLevel::NOTICE, 'Site configuration created');
                    // @extensionScannerIgnoreLine
                    $siteData->addMessage($this->translate('generate.success.createSiteConfiguration', [
                        $siteData->getDomain()
                    ]));
                } catch (SiteValidationErrorException $e) {
                    $this->log(LogLevel::ERROR, 'Cannot create site configuration for domain : ' . $siteData->getDomain());
                    throw new SiteValidationErrorException($this->translate('wizard.createSiteConfiguration.error'));
                }
            } else {
                $this->log(LogLevel::WARNING, 'The selected model does not contains root pages, no site configuration created');
                // @extensionScannerIgnoreLine
                $siteData->addMessage($this->translate('wizard.createSiteConfiguration.error.noRooTPage'));
            }
        }
    }
}
