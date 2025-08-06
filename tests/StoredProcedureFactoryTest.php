<?php

namespace Vallen\StoredProcedureFactory\Tests;

use PHPUnit\Framework\TestCase;
use Vallen\StoredProcedureFactory\StoredProcedureFactory;
use ReflectionClass;

class StoredProcedureFactoryTest extends TestCase
{
    private StoredProcedureFactory $factory;
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        $this->factory = new StoredProcedureFactory(
            hostname: 'test-server',
            username: 'test-user',
            pwd: 'test-password'
        );
        $this->reflection = new ReflectionClass($this->factory);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(StoredProcedureFactory::class, $this->factory);
    }

    public function testFormatPdoPropertiesWithEmptyParams(): void
    {
        $method = $this->reflection->getMethod('formatPdoProperties');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->factory, []);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFormatPdoPropertiesWithParams(): void
    {
        $method = $this->reflection->getMethod('formatPdoProperties');
        $method->setAccessible(true);
        
        $params = [
            'userId' => 123,
            'userName' => 'testuser'
        ];
        
        $result = $method->invoke($this->factory, $params);
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        
        $this->assertEquals('@userId', $result[0]->parameter);
        $this->assertEquals(':userid', $result[0]->placeholder);
        $this->assertEquals(123, $result[0]->value);
        
        $this->assertEquals('@userName', $result[1]->parameter);
        $this->assertEquals(':username', $result[1]->placeholder);
        $this->assertEquals('testuser', $result[1]->value);
    }

    public function testFormatPdoQueryWithoutParams(): void
    {
        $method = $this->reflection->getMethod('formatPdoQuery');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->factory, 'TestProcedure', []);
        
        $this->assertEquals('EXEC [dbo].[TestProcedure]', $result);
    }

    public function testFormatPdoQueryWithParams(): void
    {
        $formatPropertiesMethod = $this->reflection->getMethod('formatPdoProperties');
        $formatPropertiesMethod->setAccessible(true);
        
        $formatQueryMethod = $this->reflection->getMethod('formatPdoQuery');
        $formatQueryMethod->setAccessible(true);
        
        $params = ['userId' => 123, 'status' => 'active'];
        $properties = $formatPropertiesMethod->invoke($this->factory, $params);
        
        $result = $formatQueryMethod->invoke($this->factory, 'TestProcedure', $properties);
        
        $expected = 'EXEC [dbo].[TestProcedure] @userId = :userid,@status = :status';
        $this->assertEquals($expected, $result);
    }

    public function testFormatSqlSrvQueryWithoutParams(): void
    {
        $method = $this->reflection->getMethod('formatSqlSrvQuery');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->factory, 'TestProcedure', []);
        
        $this->assertEquals('{call [dbo].[TestProcedure]', $result);
    }

    public function testFormatSqlSrvQueryWithParams(): void
    {
        $method = $this->reflection->getMethod('formatSqlSrvQuery');
        $method->setAccessible(true);
        
        $params = ['param1', 'param2'];
        $result = $method->invoke($this->factory, 'TestProcedure', $params);
        
        $this->assertEquals('{call [dbo].[TestProcedure](?,?)}', $result);
    }

    public function testUtf8EncodeArrayWithValidUtf8(): void
    {
        $method = $this->reflection->getMethod('utf8EncodeArray');
        $method->setAccessible(true);
        
        $input = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'nested' => [
                'city' => 'New York'
            ]
        ];
        
        $result = $method->invoke($this->factory, $input);
        
        $this->assertEquals($input, $result);
    }

    public function testUtf8EncodeArrayWithNonUtf8(): void
    {
        $method = $this->reflection->getMethod('utf8EncodeArray');
        $method->setAccessible(true);
        
        // Create a string with non-UTF8 encoding
        $nonUtf8String = mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');
        
        $input = [
            'name' => $nonUtf8String,
            'nested' => [
                'description' => $nonUtf8String
            ]
        ];
        
        $result = $method->invoke($this->factory, $input);
        
        // The method should encode non-UTF8 strings
        $this->assertNotEquals($input, $result);
        $this->assertTrue(mb_check_encoding($result['name'], 'UTF-8'));
        $this->assertTrue(mb_check_encoding($result['nested']['description'], 'UTF-8'));
    }
}