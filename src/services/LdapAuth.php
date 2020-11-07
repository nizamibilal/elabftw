<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\AuthResponse;
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Models\Users;
use LdapRecord\Connection;

/**
 * LDAP auth service
 */
class LdapAuth implements AuthInterface
{
    /** @var Connection $connection */
    private $connection;

    /** @var string $email */
    private $email = '';

    /** @var string $password */
    private $password = '';

    /** @var AuthResponse $AuthResponse */
    private $AuthResponse;

    /** @var array $configArr */
    private $configArr;

    public function __construct(Connection $connection, array $configArr, string $email, string $password)
    {
        $this->connection = $connection;
        $this->configArr = $configArr;
        $this->email = Filter::sanitize($email);
        $this->password = $password;
        $this->AuthResponse = new AuthResponse('ldap');
    }

    public function tryAuth(): AuthResponse
    {
        $query = $this->connection->query()->setDn($this->configArr['ldap_base_dn']);
        $record = $query->findbyOrFail('mail', $this->email);
        $cn = $record['cn'][0];
        if (!$this->connection->auth()->attempt('cn=' . $cn . ',' . $this->configArr['ldap_base_dn'], $this->password)) {
            throw new InvalidCredentialsException();
        }
        $Users = new Users();
        try {
            $Users->populateFromEmail($this->email);
        } catch (ResourceNotFoundException $e) {
            // the user doesn't exist yet in the db
            // GET FIRSTNAME AND LASTNAME
            $firstname = $record[$this->configArr['ldap_firstname']][0];
            $lastname = $record[$this->configArr['ldap_lastname']][0];
            // GET TEAMS
            $teams = $record[$this->configArr['ldap_team']][0];
            // CREATE USER (and force validation of user)
            $Users = new Users($Users->create($this->email, $teams, $firstname, $lastname, '', null, true));
        }

        $this->AuthResponse->userid = (int) $Users->userData['userid'];
        $this->AuthResponse->mfaSecret = $Users->userData['mfa_secret'];
        $this->AuthResponse->setTeams();

        return $this->AuthResponse;
    }
}