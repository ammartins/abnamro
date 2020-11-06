<?php

namespace TransactionsBundle\Repository;

use Doctrine\ORM\EntityRepository;
use CategoriesBundle\Entity\Categories;

/**
 * TransactionsRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TransactionsRepository extends EntityRepository
{
    public function getMonths($year)
    {
        $months = $this
            ->getEntityManager()
            ->createQuery(
                "SELECT DISTINCT Month(p.createAt) as months
                FROM TransactionsBundle:Transactions p
                WHERE Year(p.createAt) = $year
                GROUP BY months
                ORDER BY months"
            )->execute();
            
        return $months;
    }

    public function getAllYears()
    {
        return $this->getEntityManager()
            ->createQuery(
                "SELECT DISTINCT YEAR(p.createAt) as year
                FROM TransactionsBundle:Transactions p
                ORDER BY year"
            )->getResult();
    }

    public function getMatchTransactions()
    {
        // Get all transactions that have a category
        return $this
            ->getEntityManager()
            ->createQuery(
                "SELECT p.id, p.createAt, p.amount, p.description, t.name
                FROM TransactionsBundle:Transactions p
                JOIN CategoriesBundle:Categories t
                WHERE p.categories = t.id"
            )->execute();
    }

    public function getTransactionByHash($hash)
    {
        $data = $this->getEntityManager()
            ->createQuery(
                "SELECT p.id
                FROM TransactionsBundle:Transactions p
                WHERE p.transactionHash = '$hash'"
            )->getOneOrNullResult();

        return $data;
    }

    /**
     * Find all transactions from given Month and Year
     */
    public function findAllByMonthYear($month, $year)
    {
        return $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('t')
            ->from('TransactionsBundle:Transactions', 't')
            ->where('Month(t.createAt) = ?1')
            ->andWhere('Year(t.createAt) = ?2')
            ->setParameter(1, $month)
            ->setParameter(2, $year)
            ->orderBy('t.createAt')
            ->orderBy('t.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find last element (Used for Redirect of Dashbaord)
     * TODO: Refactor this code
     */
    public function findLastOne()
    {
        return $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('t')
            ->from('TransactionsBundle:Transactions', 't')
            ->orderBy('t.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all month and year group by day and category type
     */
    public function findAllGroupByDay($month, $year)
    {
        return $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select(
                't.id,
                count(t.id) as transacs,
                Day(t.createAt) as dia,
                sum(t.amount) as cost'
            )
            ->from('TransactionsBundle:Transactions', 't')
            ->where('Month(t.createAt) = ?1')
            ->andWhere('Year(t.createAt) = ?2')
            ->setParameter(1, $month)
            ->setParameter(2, $year)
            ->orderBy('t.createAt')
            ->groupBy('dia, t.categories')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get Average payments of recurring categories
     */
    public function getAveragePayments()
    {
        return $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select(
                'count(t.id) as nOfPayments,
                DAY(t.createAt) as day,
                sum(t.amount) as total,
                avg(t.amount) as median'
            )
            ->from('TransactionsBundle:Transactions', 't')
            ->where('YEAR(t.createAt) > 2016')
            ->groupBy('day')
            ->getQuery()
            ->getResult();
    }

    /**
     * Expenses per category and number of transactions of that category
     * Monthh and Year
     */
    public function getDescriptionUsage($month, $year)
    {
        return $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select(
                'c.name as shortDescription,
                sum(t.amount) as total,
                count(t.categories) as ocurrencies,
                c'
            )
            ->from('TransactionsBundle:Transactions', 't')
            ->join('CategoriesBundle:Categories', 'c', 'with', 'c.id = t.categories')
            ->where('Month(t.createAt) = ?1')
            ->andWhere('Year(t.createAt) = ?2')
            ->andWhere('c.name != ?3')
            ->setParameter(1, $month)
            ->setParameter(2, $year)
            ->setParameter(3, '')
            ->groupBy('c.name, t.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * select endsaldo, Day(create_at) from transactions where Year(create_at) = 2019 and Month(create_at) = 7;
     */
    public function getSaldo($month, $year)
    {
        return $this
        ->getEntityManager()
        ->createQueryBuilder()
        ->select(
            't.endsaldo,
            Day(t.createAt) as day'
        )
        ->from('TransactionsBundle:Transactions', 't')
        ->where('Month(t.createAt) = ?1')
        ->andWhere('Year(t.createAt) = ?2')
        ->setParameter(1, $month)
        ->setParameter(2, $year)
        ->getQuery()
        ->getResult();
    }
}
