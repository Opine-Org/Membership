<?php
namespace Opine;

class MembershipTest extends \PHPUnit_Framework_TestCase {
    private $membership;
    private $db;
    private $userNonExistantID = '5314f7553698bb5228b15cc2';
    private $userNonMemberID = '5314f6e73698bb5228b15cc0';
    private $userMemberActiveID = '5314f0613698bb5228b15cbe';
    private $userMemberActiveExpiredID = '5314f7193698bb5228b15cc1';
    private $userMemberLapsedID = '5314f0623698bb5228b15cbf';
    private $userBecomesMember = false;

    public function setup () {
        date_default_timezone_set('America/New_York');
        $root = getcwd();
        $container = new Container($root, $root . '/container.yml');
        $this->membership = $container->membership;
        $this->db = $container->db;
        $this->userNonExistantID = new \MongoId($this->userNonExistantID);
        $this->userNonMemberID = new \MongoId($this->userNonMemberID);
        $this->userMemberActiveID = new \MongoId($this->userMemberActiveID);
        $this->userMemberActiveExpiredID = new \MongoId($this->userMemberActiveExpiredID);
        $this->userMemberLapsedID = new \MongoId($this->userMemberLapsedID);
        $this->userBecomesMember = $this->userNonMemberID;

        //active member
        $this->db->collection('users')->update(
            ['_id' => $this->userMemberActiveID],
            [
                '_id' => $this->userMemberActiveID,
                'first_name' => 'Active',
                'last_name' => 'Member',
                'email' => 'test@domain.com',
                'membership' => [
                    'lifetime' => false,
                    'expiration' => new \MongoDate(strtotime('+1 month')),
                    'term' => '+1 year',
                    'years' => [date('Y')],
                    'tierId' => null,
                    'status' => 'active'
                ],
                'groups' => ['Members']
            ],
            ['upsert' => true]
        );

        //active, but expired
        $this->db->collection('users')->update(
            ['_id' => $this->userMemberActiveExpiredID],
            [
                '_id' => $this->userMemberActiveExpiredID,
                'first_name' => 'Active',
                'last_name' => 'But Expired Member',
                'email' => 'test@domain.com',
                'membership' => [
                    'lifetime' => false,
                    'expiration' => new \MongoDate(strtotime('yesterday')),
                    'term' => '+1 year',
                    'years' => [date('Y')],
                    'tierId' => null,
                    'status' => 'active'
                ],
                'groups' => ['Members']
            ],
            ['upsert' => true]
        );

        //non member
        $this->db->collection('users')->update(
            ['_id' => $this->userNonMemberID],
            [
                '_id' => $this->userNonMemberID,
                'first_name' => 'Non',
                'last_name' => 'Member',
                'email' => 'test@domain.com'
            ],
            ['upsert' => true]
        );

        //lapsed member
        $this->db->collection('users')->update(
            ['_id' => $this->userMemberLapsedID],
            [
                '_id' => $this->userMemberLapsedID,
                'first_name' => 'Member',
                'last_name' => 'Lapsed',
                'email' => 'test@domain.com',
                'membership' => [
                    'lifetime' => false,
                    'expiration' => new \MongoDate(strtotime('-1 month')),
                    'term' => '+1 year',
                    'years' => [date('Y')],
                    'tierId' => null,
                    'status' => 'lapsed'
                ],
                'groups' => []
            ],
            ['upsert' => true]
        );
    }

    public function testUserCheckNonExistant () {
        $result = $this->membership->userCheck($this->userNonExistantID);
        $this->assertFalse($result);
    }

    public function testUserCheckNonMember () {
        $result = $this->membership->userCheck($this->userNonMemberID);
        $this->assertFalse($result);
    }

    public function testUserCheckExistant () {
        $result = $this->membership->userCheck($this->userMemberActiveID);
        $this->assertTrue($result);
    }

    public function testUserCheckExistantWithArray () {
        $membershipData = [];
        $result = $this->membership->userCheck($this->userMemberActiveID, $membershipData);
        $this->assertGreaterThan(1, count($membershipData));
    }

    public function testUserBecomesMember () {
        $result = $this->membership->userCheck($this->userBecomesMember);
        $this->assertFalse($result);
        $this->membership->userJoinOrExtend($this->userBecomesMember, null);
        $result = $this->membership->userCheck($this->userBecomesMember);
        $this->assertTrue($result);
    }

    public function testUserNewMemberExpirationOneYear () {
        $membershipData = [];
        $result = $this->membership->userCheck($this->userBecomesMember);
        $this->assertFalse($result);
        $this->membership->userJoinOrExtend($this->userBecomesMember, null);
        $result = $this->membership->userCheck($this->userBecomesMember, $membershipData);
        $this->assertTrue($result);
        $dateStored = date('Y-m-d', $membershipData['expiration']->sec);
        $dateCompared = date('Y-m-d', strtotime('+1 year'));
        $equal = false;
        if ($dateStored == $dateCompared) {
            $equal = true;
        }
        $this->assertTrue($equal);
    }

    public function testUserExistingMemberExpirationBeyondOneYear () {
        $membershipData = [];
        $result = $this->membership->userCheck($this->userMemberActiveID);
        $this->assertTrue($result);
        $this->membership->userJoinOrExtend($this->userMemberActiveID, null);
        $result = $this->membership->userCheck($this->userMemberActiveID, $membershipData);
        $this->assertTrue($result);
        $dateStored = date('U', $membershipData['expiration']->sec);
        $dateCompared = date('U', strtotime('+1 year'));
        $greater = false;
        if ($dateStored > $dateCompared) {
            $greater = true;
        }
        $this->assertTrue($greater);   
    }

    public function testUserLapsedMemberExpirationOneYear () {
        $membershipData = [];
        $result = $this->membership->userCheck($this->userMemberLapsedID);
        $this->assertTrue($result);
        $this->membership->userJoinOrExtend($this->userMemberLapsedID, null);
        $result = $this->membership->userCheck($this->userMemberLapsedID, $membershipData);
        $this->assertTrue($result);
        $dateStored = date('Y-m-d', $membershipData['expiration']->sec);
        $dateCompared = date('Y-m-d', strtotime('+1 year'));
        $equal = false;
        if ($dateStored == $dateCompared) {
            $equal = true;
        }
        $this->assertTrue($equal);
    }

    public function testUserIsExpiredNotLapsed () {
        $membershipData = [];
        $result = $this->membership->userCheck($this->userMemberActiveExpiredID, $membershipData);
        $this->assertTrue($result);
        $dateStored = date('Y-m-d', $membershipData['expiration']->sec);
        $dateCompared = date('Y-m-d', strtotime('now'));
        $expiredNotLapsed = false;
        if ($dateStored < $dateCompared && $membershipData['status'] == 'active') {
            $expiredNotLapsed = true;
        }
        $this->assertTrue($expiredNotLapsed);   
    }

    public function testUserIsExpiredMarkedLapsed () {
        $this->membership->userMarkLapsed($this->userMemberActiveExpiredID);
        $user = $this->db->collection('users')->findOne([
            '_id' => $this->userMemberActiveExpiredID
        ]);
        $lapsed = false;
        if ($user['membership']['status'] == 'lapsed' && !in_array('Members', $user['groups'])) {
            $lapsed = true;
        }
        $this->assertTrue($lapsed);   
    }

    public function testUsersComplianceExpire () {
        $countStart = $this->db->collection('users')->find(
            [
                'membership.lifetime' => false,
                'membership.status' => 'active',
                'membership.expiration' => ['$lt' => new \MongoDate(strtotime('now'))] 
            ],
            []
        )->count();
        $this->membership->usersCheckCompliance();
        $countEnd = $this->db->collection('users')->find(
            [
                'membership.lifetime' => false,
                'membership.status' => 'active',
                'membership.expiration' => ['$lt' => new \MongoDate(strtotime('now'))] 
            ],
            []
        )->count();
        $decreased = false;
        if ($countStart > $countEnd) {
            $decreased = true;
        }
        $this->assertTrue($decreased);
    }
}