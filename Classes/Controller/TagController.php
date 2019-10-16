<?php
namespace GeorgRinger\News\Controller;

/**
 * This file is part of the "news" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use GeorgRinger\News\Domain\Repository\NewsRepository;
use GeorgRinger\News\Domain\Repository\TagRepository;
use GeorgRinger\News\Utility\TypoScript;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Service\FlexFormService;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Tag controller
 */
class TagController extends NewsController
{
    const SIGNAL_TAG_LIST_ACTION = 'listAction';

    /**
     * List tags
     *
     * @param array $overwriteDemand
     */
    public function listAction(array $overwriteDemand = null)
    {
    	if( 0 ){
			$start = microtime(true);
			$tagDemand = $this->createDemandObjectFromSettings($this->settings);
			$tagDemand->setActionAndClass(__METHOD__, __CLASS__);
			$tagRepository = GeneralUtility::makeInstance(TagRepository::class);
			$newsSettings = GeneralUtility::makeInstance(FlexFormService::class)
				->convertFlexFormContentToArray($GLOBALS['TSFE']->sys_page->getRawRecord('tt_content', '77')['pi_flexform'])['settings'];
			$newsDemand = $this->createDemandObjectFromSettings($newsSettings);
			$newsDemand->setTags($this->settings['tags']);
			$newsDemand->setActionAndClass('listAction', 'NewsController');
			DebuggerUtility::var_dump($tagRepository->getTagsWithNewsDemand($tagDemand, $newsDemand));
			DebuggerUtility::var_dump(microtime(true) - $start);
			exit;
    	} else {
			// Default value is wrong for tags
			if ($this->settings['orderBy'] === 'datetime') {
				unset($this->settings['orderBy']);
			}

			$demand = $this->createDemandObjectFromSettings($this->settings);
			$demand->setActionAndClass(__METHOD__, __CLASS__);

			if ($overwriteDemand !== null && $this->settings['disableOverrideDemand'] != 1) {
				$demand = $this->overwriteDemandObject($demand, $overwriteDemand);
			}

			$assignedValues = [
				'tags' => $this->tagRepository->findDemanded($demand),
				'overwriteDemand' => $overwriteDemand,
				'demand' => $demand,
			];

			$assignedValues = $this->emitActionSignal('TagController', self::SIGNAL_TAG_LIST_ACTION, $assignedValues);
			$this->view->assignMultiple($assignedValues);
		}
    }
}
