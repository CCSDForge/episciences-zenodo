<?php

namespace App\Repository;

use App\Entity\LogUserAction;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use function PHPUnit\Framework\throwException;

/**
 * @method LogUserAction|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogUserAction|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogUserAction[]    findAll()
 * @method LogUserAction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogUserActionRepository extends ServiceEntityRepository
{
    private PaginatorInterface $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        $this->paginator = $paginator;
        parent::__construct($registry, LogUserAction::class);
    }
    public function isExistingDeposit($username,$idDoiVersion){
        //Exclusively get deposit created in app
        try {
            $query = $this->createQueryBuilder('a')
                ->where('a.username = :username')
                ->andWhere('a.doi_deposit_version = :doi_deposit_version')
                ->setParameters(new ArrayCollection([
                    new Parameter(':username', $username),
                    new Parameter(':doi_deposit_version',$idDoiVersion),
                ]))
                ->getQuery();
            return $query->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return throwException($e->getMessage());
        }
    }

    public function addLog(array $logInfo){
        $existingLog = $this->getlog([
            'username'=>$logInfo['username'],
            'doiFix'=>$logInfo['doi_deposit_fix'],
            'doiVer' => $logInfo['doi_deposit_version'],
        ]);
        $entityManager = $this->getEntityManager();
        $logUserObject = new LogUserAction();
        if (!is_null($existingLog)){
            $existingLog->setZenodoTitle($logInfo['zen_title']);
            $existingLog->setAction($logInfo['action']);
            $existingLog->setUpdatedDate(new DateTime());
            $entityManager->persist($existingLog);
            $entityManager->flush();
        }else{

            $logUserObject->setUsername($logInfo['username']);
            $logUserObject->setDoiDepositFix($logInfo['doi_deposit_fix']);
            $logUserObject->setDoiDepositVersion($logInfo['doi_deposit_version']);
            $logUserObject->setCreatedDate($logInfo['date']);
            $logUserObject->setUpdatedDate(new DateTime());
            $logUserObject->setAction($logInfo['action']);
            $logUserObject->setZenodoTitle($logInfo['zen_title']);
            try {
                $entityManager->persist($logUserObject);
                $entityManager->flush();
            } catch (Exception $e){
                return throwException($e->getMessage());
            }
        }
        return true;
    }

    public function getlog(array $informations){
        try {
            $query = $this->createQueryBuilder('a')
                ->where('a.username = :username')
                ->andWhere('a.doi_deposit_fix = :doi_deposit_fix')
                ->andWhere('a.doi_deposit_version = :doi_deposit_version')
                ->setParameters(new ArrayCollection([
                    new Parameter(':username', $informations['username']),
                    new Parameter(':doi_deposit_fix', $informations['doiFix']),
                    new Parameter(':doi_deposit_version', $informations['doiVer']),
                ]))
                ->getQuery();
            return $query->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return throwException($e->getMessage());
        }
    }

    public function getListDepositByUser($casusername,$request){

        $query = $this->createQueryBuilder('a')->select('a.doi_deposit_version','a.zenodo_title', 'a.updated_date')
            ->andWhere('a.username = :casusername')
            ->orderBy('a.updated_date','DESC')
            ->setParameter('casusername', $casusername)
            ->getQuery();

        return $this->paginator->paginate($query, $request->query->getInt('page', 1),5);
    }
}
