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
use TYPO3\CMS\Core\DataHandling\DataHandler;
use Psr\Log\LogLevel;
use Oktopuce\SiteGenerator\Dto\BaseDto;

/**
 * StateCreateFileMount
 */
class StateCreateFileMount extends StateBase implements SiteGeneratorStateInterface
{
    /**
     * Create file mount for foler create in previous step
     *
     * @param SiteGeneratorWizard $context
     * @return void
     * @throws \Exception
     */
    public function process(SiteGeneratorWizard $context): void
    {
        // Create file mount for site
        $mountId = $this->createFileMount($context->getSiteData());

        $context->getSiteData()->setMountId($mountId);
    }

    /**
     * Create file mount for site
     *
     * @param BaseDto $siteData New site data
     * @throws \Exception
     * @return int The uid of the mounted file
     */
    protected function createFileMount(BaseDto $siteData): int
    {
        $baseFolderName = $siteData->getBaseFolderName();

        // Create a new file mount at root page
        $data = [];
        $newUniqueId = 'NEW' . uniqid();
        $path = '/' . ($baseFolderName ? $baseFolderName . '/' : '') . strtolower($siteData->getTitleSanitize()) . '/';
        $data['sys_filemounts'][$newUniqueId] = [
            'title' => $siteData->getTitle(),
            'base' => 1,    /* fileadmin */
            'path' => $path,
            'pid' => 0
        ];

        /* @var $dataHandler DataHandler */
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        $tce->stripslashes_values = 0;
        $tce->start($data, []);
        $tce->process_datamap();

        // Retrieve uid of mount point created
        $mountId = $tce->substNEWwithIDs[$newUniqueId];

        if ($mountId > 0) {
            $this->log(LogLevel::NOTICE, 'Create file mount successfull (uid = ' . $mountId);
            // @extensionScannerIgnoreLine
            $siteData->addMessage($this->translate('generate.success.createFileMount', [$path, $mountId]));
        }
        else {
            $this->log(LogLevel::ERROR, 'Create file mount error');
            throw new \RuntimeException($this->translate('wizard.fileMount.error'));
        }

        return ($mountId);
    }

}
