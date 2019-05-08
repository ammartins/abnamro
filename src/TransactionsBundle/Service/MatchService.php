<?php

namespace TransactionsBundle\Service;

use TransactionsBundle\Entity\Transactions;
use CategoriesBundle\Entity\Categories;
use CategoriesBundle\Repository\CategoriesRepository;
use TransactionsBundle\Repository\TransactionsRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Doctrine\ORM\EntityManager;

class MatchService
{
    /**
     * @var \TransactionsBundle\Repository\TransactionsRepository
     */
    protected $transactionsRepository;
    protected $categoryRepository;
    protected $entityManager;

    public function __construct(
        TransactionsRepository $transactionsRepository,
        CategoriesRepository $categoryRepository,
        EntityManager $entityManager
    ) {
        $this->transactionsRepository = $transactionsRepository;
        $this->categoryRepository = $categoryRepository;
        $this->entityManager = $entityManager;
    }

    public function match($matches, $openTransaction, $category)
    {
        if ($openTransaction->getCategories() != null) {
            return;
        }

        $results = array();
        $transactions = array();

        $category = $this->categoryRepository->findOneById($category);
        $matchDescription = $this->cleanUp($openTransaction->getDescription());

        foreach ($matches as $match) {
            /*
             * If matches come from UI
             * TODO SHOULD CLEAN THIS
             */
            if (gettype($match) === 'array') {
                $match = $this
                    ->entityManager
                    ->getRepository('TransactionsBundle:Transactions')
                    ->findOneById($match['id']);
            }

            if ($category->getAccountId() != $match->getAccountId()) {
                continue;
            }

            $score = 0;
            $special = 0;

            $itemDescription = $this->cleanUp($match->getDescription());
            $customRegex = $category->getCustomRegex();

            // If Category contains a custom regex just match against it
            if ($category->getCustomRegex() &&
                preg_match(
                    "/$customRegex/",
                    $openTransaction->getDescription()
                )
            ) {
                $openTransaction->setCategories($category);
                $openTransaction->setMatchPercentage(100);

                $this->entityManager->persist($openTransaction);
                $this->entityManager->flush();

                continue;
            }

            foreach ($itemDescription as $item1) {
                if (in_array($item1, $matchDescription)) {
                    $score += 1;
                }
            }

            $matchPercent = round((($score*100)/(count($itemDescription))), 0);

            if ($matchPercent >= 100 ||
                (
                    $matchPercent >= 90 &&
                    $match->getAmount() == $openTransaction->getAmount()
                )
            ) {
                $openTransaction->setCategories($category);
                $openTransaction->setMatchPercentage($matchPercent);

                $this->entityManager->persist($openTransaction);
                $this->entityManager->flush();

                $score = $special = 0;

                $results[] = $category;

                break;
            }

            if ($matchPercent >= 80) {
                $results[] = $category->getName();
                $match->setMatchPercentage($matchPercent);
                $openTransaction->setMatchPercentage($matchPercent);
                $transactions[] = $match;
                continue;
            }
        };
        return array($results, $transactions);
    }

    public function cleanUp($description)
    {
        $description = preg_replace(
            "/\w{3,}\s+\w+\:[A-Z0-9]+\s+[0-9]{2,}.[0-9]{2,}.[0-9]{2,}\/[0-9]{2,}\.[0-9]{2,}\s/",
            '',
            $description
        );
        $description = preg_replace("/,\w{4,}/", '', $description);
        $description = preg_replace("/SEPA Incasso algemeen doorlopend Incassant:/", '', $description);
        $description = preg_replace('/(\d{1,2}[.\/])+\d{1,2}/', '', $description);
        $description = preg_replace("/\d\d-\d\d-\d\d\d\d/", "", $description);
        $description = preg_replace('/\w+:[A-Z0-9]+/', '', $description);
        $description = preg_replace("/,PAS[0-9]{3}/", '', $description);
        $description = array_filter(preg_split('/[\s\/\*]/', $description));

        foreach ($description as $key => $item1) {
            if ($item1 == 'TRTP' || $item1 == 'IBAN'
                || $item1 == 'BIC' || $item1 == 'NAME'
                || $item1 == 'EREF' || $item1 == 'SEPA'
                || $item1 == 'REMI' || $item1 == 'CSID'
                || $item1 == 'Incasso' || $item1 == 'MARF'
                || $item1 == '' || $item1 == 'algemeen'
                || $item1 == 'doorlopend' || $item1 == 'IBAN:'
                || $item1 == 'Overboeking' || $item1 == 'INGBNL2A'
                || $item1 == 'BIC:' || $item1 == 'Omschrijving:'
                || $item1 == 'SEPA' || $item1 == 'OVERBOEKING'
                || $item1 == 'BEA' || $item1 == 'NOTPROVIDED'
                || $item1 == 'Naam:' || preg_match('/PAS[0-9]{3}/', $item1)
            ) {
                unset($description[$key]);
                continue;
            }
        }

        return $description;
    }

    /**
     *
     * Match all open transactions against exiting transactions of type X
     */
    public function matchToClean($compareTo, $openTransactions)
    {
        $results = array();
        $categorieDesc = $this->cleanUp($compareTo->getDescription());

        foreach ($openTransactions as $item) {
            if ($item->getCategories()) {
                continue;
            }

            $score = 0;
            $special = 0;

            $customRegex = $compareTo->getCategories()->getCustomRegex();

            // If Category contains a custom regex just match against it
            if ($compareTo->getCategories()->getCustomRegex() &&
                preg_match(
                    "/$customRegex/",
                    $item->getDescription()
                )
            ) {
                $item->setMatchPercentage(100);
                $results[$item->getId()] = $item;
                continue;
            }

            $itemDescription = preg_replace('!\s+!', ' ', $item->getDescription());
            $itemDescription = preg_split('/[\s\/\*]/', $itemDescription);
            $itemDescription = $this->cleanUp($item->getDescription());

            foreach ($itemDescription as $item1) {
                if (in_array(strtolower($item1), array_map('strtolower', $categorieDesc))
                ) {
                    $score += 1;
                }
            }

            $matchPercent = round((($score*100)/(count($itemDescription))), 0);

            if ($matchPercent > 50) {
                $item->setMatchPercentage($matchPercent);
                $results[$item->getId()] = $item;
                $score = 0;
                $special = 0;
                continue;
            }
        }

        return $results;
    }
}
