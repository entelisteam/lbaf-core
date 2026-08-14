<?php

declare(strict_types=1);

namespace EntelisTeam\Lbaf\Core\Rector\Migration;

use Rector\Configuration\RectorConfigBuilder;
use Rector\Renaming\Rector\Name\RenameClassRector;


final class Migration_20260814_1200_MoveLbafNamespaceToLbafCore
{
    public static function apply(RectorConfigBuilder $config): RectorConfigBuilder
    {

        $classList = [
            'ControllerProxy',
            'Application\\AbstractApplication',
            'Application\\Profiler',
            'Application\\RunSequence\\RunSequenceItem',
            'Config\\Config',
            'Controller\\AbstractController',
            'Controller\\AbstractApiController',
            'Controller\\AbstractCliController',
            'Controller\\AbstractWebController',
            'Container\\Container',
            'Container\\ContainerInterface',
            'Container\\ContainerTrait',
            'Container\\InjectionResolver',
            'Container\\Attribute\\Inject',
            'Container\\Attribute\\InjectConfig',
            'Container\\Attribute\\InjectCookie',
            'Container\\Attribute\\InjectEnv',
            'Container\\Attribute\\InjectGet',
            'Container\\Attribute\\InjectHeader',
            'Container\\Attribute\\InjectPost',
            'Container\\Attribute\\InjectValue',
            'Container\\Attribute\\InjectValueAbstract',
            'Container\\Attribute\\InjectValueArrayAbstract',
            'Container\\Exception\\ContainerException',
            'Container\\Exception\\InjectArgumentTypeException',
            'Container\\Exception\\InjectArrayTypeUnspecifiedException',
            'Container\\Exception\\InjectRequiredArgumentException',
            'Daemon\\Daemon',
            'Daemon\\DaemonOptions',
            'Database\\Redis',
            'Database\\RedisConfig',
            'Database\\Rabbit',
            'Database\\RabbitConfig',
            'Database\\RabbitMessageActionEnum',
            'Database\\RabbitWorkerOptions',
            'View\\AbstractView',
            'Service\\AbstractService',
            'Service\\ServiceInfo',
            'Helper\\Console',
            'Helper\\Curl',
            'Helper\\debug',
            'Helper\\html',
            'Helper\\input',
            'Helper\\Number',
            'Helper\\PerformanceTester',
            'Helper\\Timer',
            'Logger\\LogProcessor',
            'Packer\\Json',
            'Packer\\PackerInterface',
            'Packer\\PackerType',
            'Response\\AbstractResponse',
            'Response\\ApiResponse',
            'Response\\CliResponse',
            'Response\\Header',
            'Response\\HeadersAlreadySendException',
            'Response\\RedirectResponse',
            'Router\\CliRouter',
            'Router\\FastRouteRouter',
            'Router\\RouteNotFoundException',
            'Router\\RouterInterface',
            'Router\\Attribute\\Route',
            'Router\\Route\\RouteGenerator',
            'Router\\Route\\RouteItem'
        ];
        $tmp = [];
        foreach ($classList as $class) {
            $tmp['Lbaf\\' . $class] = 'EntelisTeam\\Lbaf\\Core\\' . $class;
        }
        $tmp['Lbaf\\Response\\HtmlResponse'] = 'EntelisTeam\\Lbaf\\Core\\WebResponse';

        return $config
            ->withConfiguredRule(RenameClassRector::class, $tmp)

            //импортируем короткие имена через use вместо FQN, удаляем устаревшие use на Lbaf-овские классы
            ->withImportNames(importNames: true, importDocBlockNames: true, importShortClasses: false, removeUnusedImports: true);
    }
}
