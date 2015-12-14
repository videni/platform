<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ContextTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    /** @var Context */
    protected $context;

    protected function setUp()
    {
        $this->configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = new Context($this->configProvider, $this->metadataProvider);
    }

    public function testVersion()
    {
        $this->assertNull($this->context->getVersion());

        $this->context->setVersion('test');
        $this->assertEquals('test', $this->context->getVersion());
        $this->assertEquals('test', $this->context->get(Context::VERSION));
    }

    public function testRequestType()
    {
        $this->assertEquals([], $this->context->getRequestType());

        $this->context->setRequestType('test');
        $this->assertEquals(['test'], $this->context->getRequestType());
        $this->assertEquals(['test'], $this->context->get(Context::REQUEST_TYPE));

        $this->context->setRequestType('another');
        $this->assertEquals(['test', 'another'], $this->context->getRequestType());
        $this->assertEquals(['test', 'another'], $this->context->get(Context::REQUEST_TYPE));

        // test that already existing type is not added twice
        $this->context->setRequestType('another');
        $this->assertEquals(['test', 'another'], $this->context->getRequestType());
        $this->assertEquals(['test', 'another'], $this->context->get(Context::REQUEST_TYPE));

        $this->context->setRequestType(['test1', 'test2']);
        $this->assertEquals(['test', 'another', 'test1', 'test2'], $this->context->getRequestType());
        $this->assertEquals(['test', 'another', 'test1', 'test2'], $this->context->get(Context::REQUEST_TYPE));
    }

    /**
     * keys of request headers should be are case insensitive
     */
    public function testRequestHeaders()
    {
        $headers = $this->context->getRequestHeaders();

        $key1   = 'test1';
        $key2   = 'test2';
        $value1 = 'value1';
        $value2 = 'value2';

        $this->assertFalse($headers->has($key1));
        $this->assertFalse(isset($headers[$key1]));
        $this->assertNull($headers->get($key1));
        $this->assertNull($headers[$key1]);

        $headers->set($key1, $value1);
        $this->assertTrue($headers->has($key1));
        $this->assertTrue(isset($headers[$key1]));
        $this->assertEquals($value1, $headers->get($key1));
        $this->assertEquals($value1, $headers[$key1]);

        $this->assertTrue($headers->has(strtoupper($key1)));
        $this->assertTrue(isset($headers[strtoupper($key1)]));
        $this->assertEquals($value1, $headers->get(strtoupper($key1)));
        $this->assertEquals($value1, $headers[strtoupper($key1)]);

        $headers->remove(strtoupper($key1));
        $this->assertFalse($headers->has($key1));
        $this->assertFalse(isset($headers[$key1]));
        $this->assertNull($headers->get($key1));
        $this->assertNull($headers[$key1]);

        $headers[strtoupper($key2)] = $value2;
        $this->assertTrue($headers->has($key2));
        $this->assertTrue(isset($headers[$key2]));
        $this->assertEquals($value2, $headers->get($key2));
        $this->assertEquals($value2, $headers[$key2]);

        unset($headers[$key2]);
        $this->assertFalse($headers->has(strtoupper($key2)));
        $this->assertFalse(isset($headers[strtoupper($key2)]));
        $this->assertNull($headers->get(strtoupper($key2)));
        $this->assertNull($headers[strtoupper($key2)]);

        $headers->set(strtoupper($key1), null);
        $this->assertTrue($headers->has($key1));
        $this->assertTrue(isset($headers[$key1]));
        $this->assertNull($headers->get($key1));
        $this->assertNull($headers[$key1]);

        $this->assertEquals(1, count($headers));
        $this->assertEquals([$key1 => null], $headers->toArray());

        $headers->clear();
        $this->assertEquals(0, count($headers));
    }

    /**
     * keys of response headers should be are case sensitive
     */
    public function testResponseHeaders()
    {
        $headers = $this->context->getResponseHeaders();

        $key1   = 'test1';
        $key2   = 'test2';
        $value1 = 'value1';
        $value2 = 'value2';

        $this->assertFalse($headers->has($key1));
        $this->assertFalse(isset($headers[$key1]));
        $this->assertNull($headers->get($key1));
        $this->assertNull($headers[$key1]);

        $headers->set($key1, $value1);
        $this->assertTrue($headers->has($key1));
        $this->assertTrue(isset($headers[$key1]));
        $this->assertEquals($value1, $headers->get($key1));
        $this->assertEquals($value1, $headers[$key1]);

        $this->assertFalse($headers->has(strtoupper($key1)));
        $this->assertFalse(isset($headers[strtoupper($key1)]));
        $this->assertNull($headers->get(strtoupper($key1)));
        $this->assertNull($headers[strtoupper($key1)]);
        $headers->remove(strtoupper($key1));
        $this->assertTrue($headers->has($key1));
        unset($headers[strtoupper($key1)]);
        $this->assertTrue($headers->has($key1));

        $headers->remove($key1);
        $this->assertFalse($headers->has($key1));
        $this->assertFalse(isset($headers[$key1]));
        $this->assertNull($headers->get($key1));
        $this->assertNull($headers[$key1]);

        $headers[$key2] = $value2;
        $this->assertTrue($headers->has($key2));
        $this->assertTrue(isset($headers[$key2]));
        $this->assertEquals($value2, $headers->get($key2));
        $this->assertEquals($value2, $headers[$key2]);

        unset($headers[$key2]);
        $this->assertFalse($headers->has($key2));
        $this->assertFalse(isset($headers[$key2]));
        $this->assertNull($headers->get($key2));
        $this->assertNull($headers[$key2]);

        $headers->set($key1, null);
        $this->assertTrue($headers->has($key1));
        $this->assertTrue(isset($headers[$key1]));
        $this->assertNull($headers->get($key1));
        $this->assertNull($headers[$key1]);

        $this->assertEquals(1, count($headers));
        $this->assertEquals([$key1 => null], $headers->toArray());

        $headers->clear();
        $this->assertEquals(0, count($headers));
    }

    public function testResponseStatusCode()
    {
        $this->assertNull($this->context->getResponseStatusCode());

        $this->context->setResponseStatusCode(500);
        $this->assertEquals(500, $this->context->getResponseStatusCode());
        $this->assertEquals(500, $this->context->get(Context::RESPONSE_STATUS_CODE));
    }

    public function testClassName()
    {
        $this->assertNull($this->context->getClassName());

        $this->context->setClassName('test');
        $this->assertEquals('test', $this->context->getClassName());
        $this->assertEquals('test', $this->context->get(Context::CLASS_NAME));
    }

    public function testLoadConfigByGetConfig()
    {
        $version      = '1.1';
        $requestType  = 'rest';
        $entityClass  = 'Test\Class';
        $configExtras = [
            new TestConfigSection('section1'),
            new TestConfigSection('section2'),
            new TestConfigExtra('extra1')
        ];

        $config         = ConfigUtil::getInitialConfig();
        $section1Config = ['test'];

        $this->context->setVersion($version);
        $this->context->setRequestType($requestType);
        $this->context->setConfigExtras($configExtras);
        $this->context->setClassName($entityClass);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                $entityClass,
                $version,
                [$requestType],
                $configExtras
            )
            ->willReturn(
                [
                    ConfigUtil::DEFINITION => $config,
                    'section1' => $section1Config
                ]
            );

        // test that a config is not loaded yet
        $this->assertFalse($this->context->hasConfig());
        $this->assertFalse($this->context->hasConfigOf('section1'));
        $this->assertFalse($this->context->hasConfigOf('section2'));

        $this->assertEquals($config, $this->context->getConfig()); // load config
        $this->assertTrue($this->context->hasConfig());
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));
        $this->assertEquals($config, $this->context->get(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));

        $this->assertTrue($this->context->hasConfigOf('section1'));
        $this->assertEquals($section1Config, $this->context->getConfigOf('section1'));
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section1'));
        $this->assertEquals($section1Config, $this->context->get(Context::CONFIG_PREFIX . 'section1'));

        $this->assertTrue($this->context->hasConfigOf('section2'));
        $this->assertNull($this->context->getConfigOf('section2'));
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section2'));
        $this->assertNull($this->context->get(Context::CONFIG_PREFIX . 'section2'));

        // test that a config is loaded only once
        $this->assertEquals($config, $this->context->getConfig());
    }

    public function testLoadConfigByGetConfigOf()
    {
        $version      = '1.1';
        $requestType  = 'rest';
        $entityClass  = 'Test\Class';
        $configExtras = [
            new TestConfigSection('section1'),
            new TestConfigSection('section2')
        ];

        $config         = ConfigUtil::getInitialConfig();
        $section1Config = ['test'];

        $this->context->setVersion($version);
        $this->context->setRequestType($requestType);
        $this->context->setConfigExtras($configExtras);
        $this->context->setClassName($entityClass);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                $entityClass,
                $version,
                [$requestType],
                $configExtras
            )
            ->willReturn(
                [
                    ConfigUtil::DEFINITION => $config,
                    'section1' => $section1Config
                ]
            );

        // test that a config is not loaded yet
        $this->assertFalse($this->context->hasConfig());
        $this->assertFalse($this->context->hasConfigOf('section1'));
        $this->assertFalse($this->context->hasConfigOf('section2'));

        $this->assertEquals($section1Config, $this->context->getConfigOf('section1')); // load config
        $this->assertTrue($this->context->hasConfigOf('section1'));
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section1'));
        $this->assertEquals($section1Config, $this->context->get(Context::CONFIG_PREFIX . 'section1'));

        $this->assertTrue($this->context->hasConfig());
        $this->assertEquals($config, $this->context->getConfig());
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));
        $this->assertEquals($config, $this->context->get(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));

        $this->assertTrue($this->context->hasConfigOf('section2'));
        $this->assertNull($this->context->getConfigOf('section2'));
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section2'));
        $this->assertNull($this->context->get(Context::CONFIG_PREFIX . 'section2'));

        // test that a config is loaded only once
        $this->assertEquals($config, $this->context->getConfig());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage A class name must be set in the context before a configuration is loaded.
     */
    public function testLoadConfigNoClassName()
    {
        $this->context->getConfig();
    }

    public function testConfigWhenItIsSetExplicitly()
    {
        $config = ConfigUtil::getInitialConfig();

        $this->context->setConfigExtras([new TestConfigSection('section1')]);
        $this->context->setClassName('Test\Class');

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->context->setConfig($config);

        $this->assertTrue($this->context->hasConfig());
        $this->assertEquals($config, $this->context->getConfig());
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));
        $this->assertEquals($config, $this->context->get(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));

        $this->assertTrue($this->context->hasConfigOf('section1'));
        $this->assertNull($this->context->getConfigOf('section1'));
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section1'));
        $this->assertNull($this->context->get(Context::CONFIG_PREFIX . 'section1'));
    }

    public function testConfigWhenItIsSetExplicitlyForSection()
    {
        $section1Config = ['test'];

        $configExtras = [
            new TestConfigSection('section1'),
            new TestConfigSection('section2')
        ];

        $this->context->setConfigExtras($configExtras);
        $this->context->setClassName('Test\Class');

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->context->setConfigOf('section1', $section1Config);

        $this->assertTrue($this->context->hasConfigOf('section1'));
        $this->assertEquals($section1Config, $this->context->getConfigOf('section1'));
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section1'));
        $this->assertEquals($section1Config, $this->context->get(Context::CONFIG_PREFIX . 'section1'));

        $this->assertTrue($this->context->hasConfigOf('section2'));
        $this->assertNull($this->context->getConfigOf('section2'));
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section2'));
        $this->assertNull($this->context->get(Context::CONFIG_PREFIX . 'section2'));

        $this->assertTrue($this->context->hasConfig());
        $this->assertNull($this->context->getConfig());
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));
        $this->assertNull($this->context->get(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetConfigOfUndefinedSection()
    {
        $this->context->setConfigExtras([new TestConfigSection('section1')]);
        $this->context->setClassName('Test\Class');

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->context->getConfigOf('undefined');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetConfigOfUndefinedSection()
    {
        $this->context->setConfigExtras([new TestConfigSection('section1')]);
        $this->context->setClassName('Test\Class');

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->context->setConfigOf('undefined', []);
    }

    /**
     * @dataProvider configSectionProvider
     */
    public function testLoadKnownSectionConfigByGetConfigOf($configSection)
    {
        $mainConfig    = ConfigUtil::getInitialConfig();
        $sectionConfig = ConfigUtil::getInitialConfig();

        $mainConfig[ConfigUtil::FIELDS]['field1']    = null;
        $mainConfig[ConfigUtil::FIELDS]['field2']    = null;
        $sectionConfig[ConfigUtil::FIELDS]['field1'] = null;

        $config = [
            ConfigUtil::DEFINITION => $mainConfig,
            $configSection => $sectionConfig
        ];

        $this->context->setClassName('Test\Class');
        // set "known" sections
        $this->context->setConfigExtras([new FiltersConfigExtra(), new SortersConfigExtra()]);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        // test that a config is not loaded yet
        $this->assertFalse($this->context->hasConfig());
        foreach ($this->context->getConfigExtras() as $configExtra) {
            if ($configExtra instanceof ConfigExtraSectionInterface) {
                $this->assertFalse($this->context->{'hasConfigOf' . lcfirst($configExtra->getName())}());
            }
        }

        $suffix = lcfirst($configSection);
        $this->assertEquals($sectionConfig, $this->context->{'getConfigOf' . $suffix}()); // load config
        $this->assertTrue($this->context->{'hasConfigOf' . $suffix}());

        $this->assertTrue($this->context->hasConfig());
        $this->assertEquals($mainConfig, $this->context->getConfig());

        foreach ($this->context->getConfigExtras() as $configExtra) {
            if ($configExtra instanceof ConfigExtraSectionInterface && $configExtra->getName() !== $configSection) {
                $this->assertTrue($this->context->{'hasConfigOf' . lcfirst($configExtra->getName())}());
                $this->assertNull($this->context->{'getConfigOf' . lcfirst($configExtra->getName())}());
            }
        }
    }

    /**
     * @dataProvider configSectionProvider
     */
    public function testConfigWhenIsSetExplicitlyForKnownSection($configSection)
    {
        $sectionConfig = ConfigUtil::getInitialConfig();

        $this->context->setClassName('Test\Class');
        // set "known" sections
        $this->context->setConfigExtras([new FiltersConfigExtra(), new SortersConfigExtra()]);

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $suffix = lcfirst($configSection);
        $this->context->{'setConfigOf' . $suffix}($sectionConfig);

        $this->assertTrue($this->context->{'hasConfigOf' . $suffix}());
        $this->assertEquals($sectionConfig, $this->context->{'getConfigOf' . $suffix}());

        foreach ($this->context->getConfigExtras() as $configExtra) {
            if ($configExtra instanceof ConfigExtraSectionInterface && $configExtra->getName() !== $configSection) {
                $this->assertTrue($this->context->{'hasConfigOf' . lcfirst($configExtra->getName())}());
                $this->assertNull($this->context->{'getConfigOf' . lcfirst($configExtra->getName())}());
            }
        }

        $this->assertTrue($this->context->hasConfig());
        $this->assertNull($this->context->getConfig());
    }

    public function configSectionProvider()
    {
        return [
            [FiltersConfigExtra::NAME],
            [SortersConfigExtra::NAME]
        ];
    }

    public function testFilters()
    {
        $testFilter = $this->getMock('Oro\Bundle\ApiBundle\Filter\FilterInterface');

        $this->assertNotNull($this->context->getFilters());

        $this->context->getFilters()->set('test', $testFilter);
        $this->assertSame($testFilter, $this->context->getFilters()->get('test'));
    }

    public function testDefaultAccessorForFilterValues()
    {
        $this->assertNotNull($this->context->getFilterValues());
        $this->assertFalse($this->context->getFilterValues()->has('test'));
        $this->assertNull($this->context->getFilterValues()->get('test'));
    }

    public function testFilterValues()
    {
        $accessor = $this->getMock('Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface');
        $this->context->setFilterValues($accessor);

        $this->assertSame($accessor, $this->context->getFilterValues());
    }

    public function testConfigExtras()
    {
        $this->assertSame([], $this->context->getConfigExtras());
        $this->assertNull($this->context->get(Context::CONFIG_EXTRAS));

        $configExtras = [new TestConfigExtra('test')];
        $this->context->setConfigExtras($configExtras);
        $this->assertEquals($configExtras, $this->context->getConfigExtras());
        $this->assertEquals($configExtras, $this->context->get(Context::CONFIG_EXTRAS));

        $this->assertTrue($this->context->hasConfigExtra('test'));
        $this->assertFalse($this->context->hasConfigExtra('another'));

        $anotherConfigExtra = new TestConfigExtra('another');
        $configExtras[]     = $anotherConfigExtra;
        $this->context->addConfigExtra($anotherConfigExtra);
        $this->assertEquals($configExtras, $this->context->getConfigExtras());
        $this->assertEquals($configExtras, $this->context->get(Context::CONFIG_EXTRAS));

        unset($configExtras[0]);
        $configExtras = array_values($configExtras);
        $this->context->removeConfigExtra('test');
        $this->assertEquals($configExtras, $this->context->getConfigExtras());
        $this->assertEquals($configExtras, $this->context->get(Context::CONFIG_EXTRAS));

        // test remove of non existing extra
        $this->context->removeConfigExtra('test');
        $this->assertEquals($configExtras, $this->context->getConfigExtras());
        $this->assertEquals($configExtras, $this->context->get(Context::CONFIG_EXTRAS));

        $this->context->setConfigExtras([]);
        $this->assertSame([], $this->context->getConfigExtras());
        $this->assertNull($this->context->get(Context::CONFIG_EXTRAS));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected an array of "Oro\Bundle\ApiBundle\Config\ConfigExtraInterface".
     */
    public function testSetInvalidConfigExtras()
    {
        $this->context->setConfigExtras(['test']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "test" config extra already exists.
     */
    public function testAddDuplicateConfigExtra()
    {
        $configExtras = [new TestConfigExtra('test')];
        $this->context->setConfigExtras($configExtras);

        $this->context->addConfigExtra(new TestConfigExtra('test'));
    }

    public function testLoadMetadata()
    {
        $version      = '1.1';
        $requestType  = 'rest';
        $entityClass  = 'Test\Class';
        $configExtras = [
            new TestConfigSection('section1'),
            new TestConfigSection('section2')
        ];

        $config         = ConfigUtil::getInitialConfig();
        $metadata       = new EntityMetadata();
        $metadataExtras = [new TestMetadataExtra('extra1')];

        $this->context->setVersion($version);
        $this->context->setRequestType($requestType);
        $this->context->setConfigExtras($configExtras);
        $this->context->setMetadataExtras($metadataExtras);
        $this->context->setClassName($entityClass);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                $entityClass,
                $version,
                [$requestType],
                $configExtras
            )
            ->willReturn([ConfigUtil::DEFINITION => $config]);
        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(
                $entityClass,
                $version,
                [$requestType],
                $metadataExtras,
                $config
            )
            ->willReturn($metadata);

        // test that metadata are not loaded yet
        $this->assertFalse($this->context->hasMetadata());

        $this->assertSame($metadata, $this->context->getMetadata()); // load metadata
        $this->assertTrue($this->context->hasMetadata());
        $this->assertTrue($this->context->has(Context::METADATA));
        $this->assertSame($metadata, $this->context->get(Context::METADATA));

        $this->assertEquals($config, $this->context->getConfig());

        // test that metadata are loaded only once
        $this->assertSame($metadata, $this->context->getMetadata());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage A class name must be set in the context before metadata are loaded.
     */
    public function testLoadMetadataNoClassName()
    {
        $this->context->getMetadata();
    }

    public function testMetadataWhenItIsSetExplicitly()
    {
        $metadata = new EntityMetadata();

        $this->context->setClassName('Test\Class');

        $this->configProvider->expects($this->never())
            ->method('getConfig');
        $this->metadataProvider->expects($this->never())
            ->method('getMetadata');

        $this->context->setMetadata($metadata);

        $this->assertTrue($this->context->hasMetadata());
        $this->assertSame($metadata, $this->context->getMetadata());
        $this->assertTrue($this->context->has(Context::METADATA));
        $this->assertSame($metadata, $this->context->get(Context::METADATA));
    }

    public function testMetadataExtras()
    {
        $this->assertSame([], $this->context->getMetadataExtras());
        $this->assertNull($this->context->get(Context::METADATA_EXTRAS));

        $metadataExtras = [new TestMetadataExtra('test')];
        $this->context->setMetadataExtras($metadataExtras);
        $this->assertEquals($metadataExtras, $this->context->getMetadataExtras());
        $this->assertEquals($metadataExtras, $this->context->get(Context::METADATA_EXTRAS));

        $this->assertTrue($this->context->hasMetadataExtra('test'));
        $this->assertFalse($this->context->hasMetadataExtra('another'));

        $anotherMetadataExtra = new TestMetadataExtra('another');
        $metadataExtras[]     = $anotherMetadataExtra;
        $this->context->addMetadataExtra($anotherMetadataExtra);
        $this->assertEquals($metadataExtras, $this->context->getMetadataExtras());
        $this->assertEquals($metadataExtras, $this->context->get(Context::METADATA_EXTRAS));

        unset($metadataExtras[0]);
        $metadataExtras = array_values($metadataExtras);
        $this->context->removeMetadataExtra('test');
        $this->assertEquals($metadataExtras, $this->context->getMetadataExtras());
        $this->assertEquals($metadataExtras, $this->context->get(Context::METADATA_EXTRAS));

        // test remove of non existing extra
        $this->context->removeMetadataExtra('test');
        $this->assertEquals($metadataExtras, $this->context->getMetadataExtras());
        $this->assertEquals($metadataExtras, $this->context->get(Context::METADATA_EXTRAS));

        $this->context->setMetadataExtras([]);
        $this->assertSame([], $this->context->getMetadataExtras());
        $this->assertNull($this->context->get(Context::METADATA_EXTRAS));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected an array of "Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface".
     */
    public function testSetInvalidMetadataExtras()
    {
        $this->context->setMetadataExtras(['test']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "test" metadata extra already exists.
     */
    public function testAddDuplicateMetadataExtra()
    {
        $metadataExtras = [new TestMetadataExtra('test')];
        $this->context->setMetadataExtras($metadataExtras);

        $this->context->addMetadataExtra(new TestMetadataExtra('test'));
    }

    public function testQuery()
    {
        $this->assertFalse($this->context->hasQuery());
        $this->assertNull($this->context->getQuery());

        $query = new \stdClass();

        $this->context->setQuery($query);
        $this->assertTrue($this->context->hasQuery());
        $this->assertSame($query, $this->context->getQuery());
        $this->assertSame($query, $this->context->get(Context::QUERY));

        $this->context->setQuery(null);
        $this->assertTrue($this->context->hasQuery());
    }

    public function testCriteria()
    {
        $this->assertNull($this->context->getCriteria());

        $criteria = $this->getMockBuilder('Oro\Bundle\ApiBundle\Collection\Criteria')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->setCriteria($criteria);
        $this->assertSame($criteria, $this->context->getCriteria());
        $this->assertSame($criteria, $this->context->get(Context::CRITERIA));
    }

    public function testErrors()
    {
        $this->assertFalse($this->context->hasErrors());
        $this->assertSame([], $this->context->getErrors());

        $this->context->addError(new Error());
        $this->assertTrue($this->context->hasErrors());
        $this->assertCount(1, $this->context->getErrors());

        $this->context->resetErrors();
        $this->assertFalse($this->context->hasErrors());
        $this->assertSame([], $this->context->getErrors());
    }
}