<?php
namespace Opine\Membership;

class Controller {
    private $person;
    private $db;

    public function __construct ($db, $person) {
        $this->db = $db;
        $this->person = $person;
    }

    public function data () {
    	$person = $this->person->current();
        if ($person === false) {
            echo json_encode([]);
            return;
        }
        $user = $this->db->collection('users')->findOne(['_id' => $this->db->id($person)]);
        if (!isset($user['membership'])) {
            echo json_encode([]);
            return;
        }
        $membership = $user['membership'];
        $membership['expiration'] = date('c', $membership['expiration']->sec);
        if (!empty($membership['tierId'])) {
            $membership['tier'] = $this->db->collection('membership_levels')->findOne(['_id' => $this->db->id($membership['tierId'])]);
        }

        $now = new \DateTime('now');
        $then = new \DateTime($membership['expiration']);
        $membership['until'] = (array)$now->diff($then);

        echo json_encode($membership, JSON_PRETTY_PRINT);
    }
}