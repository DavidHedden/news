<?php

namespace GeorgRinger\News\Domain\Repository;

/**
 * This file is part of the "news" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */
use GeorgRinger\News\Domain\Model\DemandInterface;
use GeorgRinger\News\Utility\Validation;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Repository for tag objects
 */
class TagRepository extends \GeorgRinger\News\Domain\Repository\AbstractDemandedRepository
{

    /**
     * Find categories by a given pid
     *
     * @param array $idList list of id s
     * @param array $ordering ordering
     * @param string $startingPoint starting point uid or comma separated list
     * @return QueryInterface
     */
    public function findByIdList(array $idList, array $ordering = [], $startingPoint = null)
    {
        if (empty($idList)) {
            throw new \InvalidArgumentException('The given id list is empty.', 1484823596);
        }
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setRespectSysLanguage(false);

        if (count($ordering) > 0) {
            $query->setOrderings($ordering);
        }

        $conditions = [];
        $conditions[] = $query->in('uid', $idList);

        if ($startingPoint !== null) {
            $conditions[] = $query->in('pid', GeneralUtility::trimExplode(',', $startingPoint, true));
        }

        return $query->matching(
            $query->logicalAnd(
                $conditions
            ))->execute();
    }

	/**
	 * @param $demand
	 * @param $newsDemand
	 * @return QueryInterface
	 */
    public function getTagsWithNewsDemand($demand, $newsDemand) {
    	$newsRepository = $this->objectManager->get(NewsRepository::class);
    	$resultSQL = $newsRepository->findDemandedRaw($newsDemand);
		$connection = GeneralUtility::makeInstance(ConnectionPool::class)
			->getConnectionForTable('tx_news_domain_model_tag');
		try {
			$res = $connection->query(
				'SELECT tag.title , IFNULL(count(tag_mm.uid_foreign),0) as `count` FROM tx_news_domain_model_tag AS tag ' .
					'LEFT JOIN (' .
						'SELECT news.title, tx_news_domain_model_news_tag_mm.uid_local, tx_news_domain_model_news_tag_mm.uid_foreign FROM tx_news_domain_model_news_tag_mm ' .
							'RIGHT JOIN (' . $resultSQL . ') AS news ON news.uid = tx_news_domain_model_news_tag_mm.uid_local'.
					') AS tag_mm ON tag_mm.uid_foreign = tag.uid ' .
				'WHERE tag.uid IN (' . $demand->getTags(). ') GROUP BY tag.title'
			);
			return $res->fetchAll();
		} catch(\Exception $e){
			DebuggerUtility::var_dump($e);
		}
	}

    /**
     * Returns an array of constraints created from a given demand object.
     *
     * @param QueryInterface $query
     * @param DemandInterface $demand
     * @return array<\TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface>
     */
    protected function createConstraintsFromDemand(QueryInterface $query, DemandInterface $demand)
    {
        $constraints = [];

        // Storage page
        if ($demand->getStoragePage() != 0) {
            $pidList = GeneralUtility::intExplode(',', $demand->getStoragePage(), true);
            $constraints[] = $query->in('pid', $pidList);
        }

        // Tags
        if ($demand->getTags()) {
            $tagList = GeneralUtility::intExplode(',', $demand->getTags(), true);
            $constraints[] = $query->in('uid', $tagList);
        }

        // Clean not used constraints
        foreach ($constraints as $key => $value) {
            if (is_null($value)) {
                unset($constraints[$key]);
            }
        }

        return $constraints;
    }

    /**
     * Returns an array of orderings created from a given demand object.
     *
     * @param DemandInterface $demand
     * @return array<\TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface>
     */
    protected function createOrderingsFromDemand(DemandInterface $demand)
    {
        $orderings = [];

        if (Validation::isValidOrdering($demand->getOrder(), $demand->getOrderByAllowed())) {
            $orderList = GeneralUtility::trimExplode(',', $demand->getOrder(), true);

            if (!empty($orderList)) {
                // go through every order statement
                foreach ($orderList as $orderItem) {
                    list($orderField, $ascDesc) = GeneralUtility::trimExplode(' ', $orderItem, true);
                    // count == 1 means that no direction is given
                    if ($ascDesc) {
                        $orderings[$orderField] = ((strtolower($ascDesc) == 'desc') ?
                            QueryInterface::ORDER_DESCENDING :
                            QueryInterface::ORDER_ASCENDING);
                    } else {
                        $orderings[$orderField] = QueryInterface::ORDER_ASCENDING;
                    }
                }
            }
        }

        return $orderings;
    }
}
