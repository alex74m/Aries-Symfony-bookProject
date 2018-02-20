<?php

namespace AppBundle\Repository;

use AppBundle\Entity\User;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends \Doctrine\ORM\EntityRepository
{
	/**
	 * Get users by search term
	 * @var String $query
	 * @return array $users
	 */
	public function ​findUserByFirstNameOrLastName($query = null)
	{
		$qb = $this->createQueryBuilder('u');

		$users = $qb->distinct()
		->where($qb->expr()->like('u.firstName', ':firstname'))
		->setParameter('firstname','%'.$query.'%')
		->orWhere($qb->expr()->like('u.lastName', ':lastname'))
		->setParameter('lastname', '%'.$query.'%')
		->getQuery()->getResult();

		return $users;
		
	}
}