<?php

namespace MahdiMorovati\MongoDbPassport\Bridge;

use MahdiMorovati\MongoDbPassport\ClientRepository as ClientModelRepository;
use MahdiMorovati\MongoDbPassport\Passport;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface
{
    /**
     * The client model repository.
     *
     * @var \MahdiMorovati\MongoDbPassport\ClientRepository
     */
    protected $clients;

    /**
     * Create a new repository instance.
     *
     * @param  \MahdiMorovati\MongoDbPassport\ClientRepository  $clients
     * @return void
     */
    public function __construct(ClientModelRepository $clients)
    {
        $this->clients = $clients;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientEntity($clientIdentifier)
    {
        $record = $this->clients->findActive($clientIdentifier);

        if (! $record) {
            return;
        }

        return new Client(
            $clientIdentifier,
            $record->name,
            $record->redirect,
            $record->confidential(),
            $record->provider
        );
    }

    /**
     * {@inheritdoc}
     */
    public function validateClient($clientIdentifier, $clientSecret, $grantType)
    {
        // First, we will verify that the client exists and is authorized to create personal
        // access tokens. Generally personal access tokens are only generated by the user
        // from the main interface. We'll only let certain clients generate the tokens.
        $record = $this->clients->findActive($clientIdentifier);

        if (! $record || ! $this->handlesGrant($record, $grantType)) {
            return false;
        }

        return ! $record->confidential() || $this->verifySecret((string) $clientSecret, $record->secret);
    }

    /**
     * Determine if the given client can handle the given grant type.
     *
     * @param  \MahdiMorovati\MongoDbPassport\Client  $record
     * @param  string  $grantType
     * @return bool
     */
    protected function handlesGrant($record, $grantType)
    {
        if (is_array($record->grant_types) && ! in_array($grantType, $record->grant_types)) {
            return false;
        }

        switch ($grantType) {
            case 'authorization_code':
                return ! $record->firstParty();
            case 'personal_access':
                return $record->personal_access_client && $record->confidential();
            case 'password':
                return $record->password_client;
            case 'client_credentials':
                return $record->confidential();
            default:
                return true;
        }
    }

    /**
     * Verify the client secret is valid.
     *
     * @param  string  $clientSecret
     * @param  string  $storedHash
     * @return bool
     */
    protected function verifySecret($clientSecret, $storedHash)
    {
        return Passport::$hashesClientSecrets
                    ? password_verify($clientSecret, $storedHash)
                    : hash_equals($storedHash, $clientSecret);
    }
}
