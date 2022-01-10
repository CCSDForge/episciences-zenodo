<?php

namespace App\Repository;

use App\Entity\LogUserAction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use function PHPUnit\Framework\throwException;

/**
 * @method LogUserAction|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogUserAction|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogUserAction[]    findAll()
 * @method LogUserAction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogUserActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogUserAction::class);
    }

    public function addLog(array $logInfo){
        $entityManager = $this->getEntityManager();
        $logUserObject = new LogUserAction();
        $logUserObject->setUsername($logInfo['username']);
        $logUserObject->setDoiDepositFix($logInfo['doi_deposit_fix']);
        $logUserObject->setDoiDepositVersion($logInfo['doi_deposit_version']);
        $logUserObject->setDate($logInfo['date']);
        $logUserObject->setAction($logInfo['action']);
        $entityManager->persist($logUserObject);
        $entityManager->flush();
        $isPersisted = $entityManager->contains($logUserObject);
        if($isPersisted){
            return true;
        }else{
            return throwException('Object not persisted');
        }
    }
}
