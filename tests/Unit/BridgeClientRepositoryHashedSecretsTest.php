<?php

namespace MahdiMorovati\MongoDbPassport\Tests\Unit;

use MahdiMorovati\MongoDbPassport\Bridge\ClientRepository as BridgeClientRepository;
use MahdiMorovati\MongoDbPassport\ClientRepository;
use MahdiMorovati\MongoDbPassport\Passport;
use Mockery as m;

class BridgeClientRepositoryHashedSecretsTest extends BridgeClientRepositoryTest
{
    protected function setUp(): void
    {
        Passport::hashClientSecrets();

        $clientModelRepository = m::mock(ClientRepository::class);
        $clientModelRepository->shouldReceive('findActive')
            ->with(1)
            ->andReturn(new BridgeClientRepositoryHashedTestClientStub);

        $this->clientModelRepository = $clientModelRepository;
        $this->repository = new BridgeClientRepository($clientModelRepository);
    }
}

class BridgeClientRepositoryHashedTestClientStub extends BridgeClientRepositoryTestClientStub
{
    public $secret = '$2y$10$WgqU4wQpfsARCIQk.nPSOOiNkrMpPVxQiLCFUt8comvQwh1z6WFMG';
}
