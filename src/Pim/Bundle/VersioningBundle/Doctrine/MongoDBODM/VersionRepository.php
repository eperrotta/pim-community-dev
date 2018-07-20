<?php

namespace Pim\Bundle\VersioningBundle\Doctrine\MongoDBODM;

use Akeneo\Component\Versioning\Model\VersionInterface;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Pim\Bundle\VersioningBundle\Repository\VersionRepositoryInterface;

/**
 * MongoDB version repository
 *
 * @author    Filips Alpe <filips@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class VersionRepository extends DocumentRepository implements VersionRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLogEntries($resourceName, $resourceId)
    {
        return $this->findBy(
            ['resourceId' => $resourceId, 'resourceName' => $resourceName, 'pending' => false],
            ['loggedAt' => 'desc']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getOldestLogEntry($resourceName, $resourceId, $pending = false)
    {
        return $this->getOneLogEntry($resourceName, $resourceId, $pending, 'asc');
    }

    /**
     * {@inheritdoc}
     */
    public function getNewestLogEntry($resourceName, $resourceId, $pending = false)
    {
        return $this->getOneLogEntry($resourceName, $resourceId, $pending, 'desc');
    }

    /**
     * {@inheritdoc}
     */
    public function getNewestLogEntryForRessources($resourceNames)
    {
        $entries = $this->findBy(['resourceName' => ['$in' => $resourceNames]], ['loggedAt' => 'desc'], 1);

        return empty($entries) ? null : current($entries);
    }

    /**
     * {@inheritdoc}
     */
    public function getPendingVersions($limit = null)
    {
        return $this->findBy(['pending' => true], ['loggedAt' => 'asc'], $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function getPendingVersionsCount()
    {
        $qb = $this->createQueryBuilder()->field('pending')->equals(true);

        return $qb->getQuery()->execute()->count();
    }

    /**
     * @param array $params
     *
     * @return \Doctrine\ODM\MongoDB\Query\Builder
     */
    public function createDatagridQueryBuilder(array $params = [])
    {
        $qb = $this->createQueryBuilder();
        $qb->field('pending')->equals(false);

        if (!empty($params['objectClass']) && !empty($params['objectId'])) {
            $qb->field('resourceName')->equals($params['objectClass']);
            $qb->field('resourceId')->equals($params['objectId']);
        }

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function findPotentiallyPurgeableBy(array $options = [])
    {
        $qb = $this->createQueryBuilder();

        if (isset($options['resource_name'])) {
            $qb->field('resourceName')->equals($options['resource_name']);
        }

        if (isset($options['date_operator']) && isset($options['limit_date'])) {
            if ('<' === $options['date_operator']) {
                $qb->field('loggedAt')->lt($options['limit_date']);
            } else {
                $qb->field('loggedAt')->gt($options['limit_date']);
            }
        }

        return $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function getNewestVersionIdForResource($resourceName, $resourceId)
    {
        $qb = $this->createQueryBuilder();
        $qb->field('resourceName')->equals($resourceName)
            ->field('resourceId')->equals($resourceId)
            ->sort('version', 'desc');

        $lastVersion = $qb->getQuery()->getSingleResult();

        return null !== $lastVersion ? $lastVersion->getId() : null;
    }

    /**
     * Get one log entry
     *
     * @param string    $resourceName
     * @param string    $resourceId
     * @param bool|null $pending
     * @param string    $sort
     *
     * @return VersionInterface|null
     */
    protected function getOneLogEntry($resourceName, $resourceId, $pending, $sort)
    {
        $criteria = ['resourceId' => (string) $resourceId, 'resourceName' => $resourceName];
        if (null !== $pending) {
            $criteria['pending'] = $pending;
        }

        $results = $this->findBy(
            $criteria,
            ['loggedAt' => $sort],
            1
        );

        return !empty($results) ? current($results) : null;
    }
}