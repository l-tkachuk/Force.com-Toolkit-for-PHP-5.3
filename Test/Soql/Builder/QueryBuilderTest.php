<?php
namespace Codemitte\ForceToolkit\Test\Soql\Builder;

use
    Codemitte\ForceToolkit\Soap\Mapping\Base\login,
    Codemitte\ForceToolkit\Soap\Client\Connection\SfdcConnection,
    Codemitte\ForceToolkit\Soap\Client\PartnerClient,
    Codemitte\ForceToolkit\Soql\Builder\QueryBuilder,
    Codemitte\ForceToolkit\Soql\Parser\QueryParser,
    Codemitte\ForceToolkit\Soql\Tokenizer\Tokenizer,
    Codemitte\ForceToolkit\Soql\Renderer\QueryRenderer,
    Codemitte\ForceToolkit\Soql\Type\TypeFactory,
    Codemitte\ForceToolkit\Soql\Builder\ExpressionBuilderInterface AS Expr
;

/**
 * @group QueryBuilder
 */
class QueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PartnerClient
     */
    private static $client;

    /**
     * @var SfdcConnection
     */
    private static $connection;

    public static function setUpBeforeClass()
    {
        self::setUpConnection();
        self::setUpClient();
    }

    private static function setUpConnection()
    {
        $credentials = new login(SFDC_USERNAME, SFDC_PASSWORD);

        $wsdl = __DIR__ . '/../../fixtures/partner.wsdl.xml';

        $serviceLocation = SFDC_SERVICE_LOCATION ? SFDC_SERVICE_LOCATION : null;

        self::$connection = new SfdcConnection($credentials, $wsdl, $serviceLocation, array(), true);
    }

    private static function setUpClient()
    {
        self::$connection->login();

        self::$client = new PartnerClient(self::$connection);
    }

    private function newBuilder()
    {
        return new QueryBuilder(self::$client, new QueryParser(new Tokenizer()), new QueryRenderer(new TypeFactory()));
    }

    public function testBuilder()
    {
        $builder = $this->newBuilder();

        $res = $builder
            ->select('Id')
            ->from('Account')
            ->where($builder
                ->whereExpr()
                ->xpr('Name', Expr::OP_NEQ, 'NULL')
                ->andXpr('AccountNumber', Expr::OP_NEQ, 'NULL')
            )
            ->limit(1)
            ->fetch();

    }

    public function testTypeofSelectClause()
    {
        $builder = $this->newBuilder();

        $soql = $builder
            ->prepareStatement('SELECT TYPEOF object1 WHEN type1 THEN field1 END FROM dings')
            ->getSoql();

        $this->assertEquals($soql, 'SELECT TYPEOF object1 WHEN type1 THEN field1 END FROM dings');

        $soql = $builder
            ->prepareStatement('SELECT name, TYPEOF object1 WHEN type1 THEN field1 END FROM dings')
            ->getSoql();

        $this->assertEquals($soql, 'SELECT name, TYPEOF object1 WHEN type1 THEN field1 END FROM dings');

        $soql = $builder
            ->prepareStatement('SELECT TYPEOF field1 WHEN type1 THEN field1 END, field2 FROM dings')
            ->getSoql();

        $this->assertEquals($soql, 'SELECT TYPEOF field1 WHEN type1 THEN field1 END, field2 FROM dings');

        $soql = $builder
            ->prepareStatement('SELECT fielda, fieldb, TYPEOF field1 WHEN type1 THEN field1 END, fieldc, fieldd FROM dings')
            ->getSoql();

        $this->assertEquals($soql, 'SELECT fielda, fieldb, TYPEOF field1 WHEN type1 THEN field1 END, fieldc, fieldd FROM dings');

        $soql = $builder
            ->prepareStatement('SELECT fielda, fieldb, TYPEOF field1 WHEN type1 THEN field1 END, TYPEOF f1 WHEN t1 THEN f2 END, fieldc, fieldd FROM dings')
            ->getSoql();

        $this->assertEquals($soql, 'SELECT fielda, fieldb, TYPEOF field1 WHEN type1 THEN field1 END, TYPEOF f1 WHEN t1 THEN f2 END, fieldc, fieldd FROM dings');
    }

    public function testSoqlFunctions()
    {
        $builder = $this->newBuilder();

        $soql = $builder
            ->prepareStatement("SELECT
                fielda, fieldb
                FROM
                    sobject1
                WHERE
                    DISTANCE(geofield__c, GEOLOCATION(37.7, -122.3), 'mi') > 3")
        ->getSoql();

        $this->assertEquals($soql, "SELECT fielda, fieldb FROM sobject1 WHERE DISTANCE(geofield__c, GEOLOCATION(37.7, -122.3), 'mi') > 3");
    }

    public function testNumber()
    {
        $builder = $this->newBuilder();

        $soql = $builder->prepareStatement('SELECT Id FROM Account WHERE Amount__c > 32.3')->getSoql();

        $this->assertEquals('SELECT Id FROM Account WHERE Amount__c > 32.3', $soql);
    }

    public function testAlias()
    {
        $res = $this->newBuilder()->prepareStatement('
                SELECT
                    b.Id,
                    b.Name,
                    b.Competitor__c,
                    b.Country__c,
                    b.ID2__c
                FROM
                    Brand__c b
                WHERE
                    b.Name = :brandname
                LIMIT 1
                ',
            array(
                'brandname' => 'Philipps'
            ))->getSoql();

        $this->assertEquals("SELECT b.Id, b.Name, b.Competitor__c, b.Country__c, b.ID2__c FROM Brand__c AS b WHERE b.Name = 'Philipps' LIMIT 1", $res);
    }

    public function testSimpleCollectionQuery()
    {
        $res = $this->newBuilder()
            ->select('Id, AccountNumber, Name')
            ->from('Account')
            ->limit(10)
            ->fetch();

        $this->assertInstanceOf('Codemitte\Soap\Mapping\GenericResultCollection', $res);
    }

    public function testSimpleSingleSobjectQuery()
    {
        $account = new \Codemitte\ForceToolkit\Soap\Mapping\Sobject('Account', array(
           'AccountNumber' =>  'test12345',
           'Name' => 'Testcompany Inc.'
        ));

        $createResponse = self::$client->create($account);

        $res = $this->newBuilder()
            ->select('Id, AccountNumber, Name')
            ->from('Account')
            ->where('Id = :id', array('id' => $createResponse->get('result')->get(0)->get('id')))
            ->fetchOne();

        $this->assertInstanceOf('\Codemitte\ForceToolkit\Soap\Mapping\Sobject', $res);

        self::$client->delete($createResponse->get('result')->get(0)->get('id'));
    }

    public function testWhereExpressionQuery()
    {
        $builder = $this->newBuilder();

        $id = 'xxxxxxxxxxxxxxxxxx';

        $res = $builder
            ->select('Id, AccountNumber, Name')
            ->from('Account')
            ->where(
                $builder
                    ->whereExpr()
                        ->xpr('Id', '=', ':id')
                        ->andXpr
                        (
                            $builder
                                ->whereExpr()
                                    ->xpr('Name', '=', "'Supercompany'")
                                    ->orXpr('AccountNumber', '=', "'12345'")
                        ),
                array('id' => $id)
            )
            ->getSoql();

        $this->assertEquals("SELECT Id, AccountNumber, Name FROM Account WHERE Id = 'xxxxxxxxxxxxxxxxxx' AND (Name = 'Supercompany' OR AccountNumber = '12345')", $res);
    }
}
