<?php

namespace Paymentwall\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;
use Shopware\Components\Theme\LessDefinition;

class Less implements SubscriberInterface
{
    protected $pluginDir;

    public function __construct($pluginDir)
    {
        $this->pluginDir = $pluginDir;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Theme_Compiler_Collect_Plugin_Less' => 'onCollectLessFiles',
        ];
    }

    public function onCollectLessFiles()
    {
        $less = new LessDefinition(
            [],
            [$this->pluginDir . '/Resources/views/frontend/_public/src/less/all.less'],
            $this->pluginDir
        );

        return new ArrayCollection([$less]);
    }
}
