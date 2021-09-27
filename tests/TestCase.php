<?php

namespace Consul\Test;

use Illuminate\Support\Arr;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Consul\Exceptions\RequestException;
use Consul\Providers\ConsulServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

/**
 * Class TestCase
 *
 * @package Consul\Test
 */
abstract class TestCase extends OrchestraTestCase
{
    /**
     * Path to emulator folder
     * @var string|null
     */
    private ?string $emulatorFolder = null;

    /**
     * List of available emulators
     * @var array
     */
    private array $emulators = [];

    /**
     * List of emulators and their respective data
     * @var array
     */
    private array $emulatedData = [];

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        $this->emulatorFolder = __DIR__ . DIRECTORY_SEPARATOR . 'Emulator';
        $this->loadEmulators();
        parent::setUp();
    }

    /**
     * @inheritDoc
     */
    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Load data for all available emulators
     * @return void
     */
    protected function loadEmulators(): void
    {
        $emulatorFiles = array_values(array_diff(scandir($this->emulatorFolder), ['.', '..']));
        foreach ($emulatorFiles as $emulatorFile) {
            $filePath = $this->emulatorFolder . DIRECTORY_SEPARATOR . $emulatorFile;
            $decodedJson = json_decode(file_get_contents($filePath), true);
            $this->emulators[Arr::get($decodedJson, 'emulates')] = $filePath;
            $this->emulatedData[Arr::get($decodedJson, 'emulates')] = Arr::get($decodedJson, 'provides');
        }
    }

    /**
     * Get emulator absolute path
     * @param string $emulatorName
     *
     * @return string|null
     */
    protected function getEmulator(string $emulatorName): ?string
    {
        if (array_key_exists($emulatorName, $this->emulators) || isset($this->emulators[$emulatorName])) {
            return $this->emulators[$emulatorName];
        }
        return null;
    }

    /**
     * Get emulated data
     * @param string $emulatorName
     *
     * @return mixed
     */
    protected function getEmulatedData(string $emulatorName): mixed
    {
        if (array_key_exists($emulatorName, $this->emulatedData) || isset($this->emulatedData[$emulatorName])) {
            return $this->emulatedData[$emulatorName];
        }
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function getPackageProviders($app): array
    {
        return [
            ConsulServiceProvider::class,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getEnvironmentSetUp($app): void
    {
        $this
            ->setConfigurationValue('app.env', 'testing', $app)
            ->setConfigurationValue('app.debug', true, $app)
            ->setConfigurationValue('cache.default', 'array', $app)
            ->setConfigurationValue('hashing.bcrypt.round', 4, $app)
            ->setConfigurationValue('database.default', 'sqlite', $app)
            ->setConfigurationValue('database.connections.sqlite', [
                'driver'    =>  'sqlite',
                'database'  =>  ':memory',
                'prefix'    =>  '',
            ], $app);
    }

    /**
     * Set application configuration value
     * @param string           $key
     * @param mixed            $value
     * @param Application|null $application
     *
     * @return static|self
     */
    private function setConfigurationValue(string $key, mixed $value, ?Application $application = null): self
    {
        $application = $application ?? $this->app;
        /**
         * @var Repository $config
         */
        $config = $application['config'];
        $config->set($key, $value);
        return $this;
    }

    /**
     * Expect not found exception
     * @return void
     */
    protected function expectNotFound(): void
    {
        $this->expectExceptionCode(404);
        $this->expectException(RequestException::class);
    }

    /**
     * Expect internal server error exception
     * @return void
     */
    protected function expectInternalServerError(): void
    {
        $this->expectExceptionCode(500);
        $this->expectException(RequestException::class);
    }
}
