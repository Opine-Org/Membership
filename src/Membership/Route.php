<?php
/**
 * Opine\Membership\Application
 *
 * Copyright (c)2013, 2014 Ryan Mahoney, https://github.com/Opine-Org <ryan@virtuecenter.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Opine\Membership;

class Route {
    private $route;
    private $root;
    private $bundleRoot;
    private $person;

    public function __construct ($container, $root, $bundleRoot) {
        $this->route = $container->route;
        $this->root = $root;
        $this->bundleRoot = $bundleRoot;
        $this->db = $container->db;
        $this->person = $container->person;
    }

    public function paths () {
        $this->route->get('/Membership/member-data', function () {
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
        });
    }

    public function build ($bundleRoot) {}

    public function upgrade ($bundleRoot) {}
}