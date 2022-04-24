<?php

namespace IceCatBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class IceCatBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    /**
     * {@inheritdoc}
     */
    public function getJsPaths()
    {
        return [
            '/bundles/icecat/js/pimcore/startup.js',
            '/bundles/icecat/js/pimcore/toolbar.js',
            '/bundles/icecat/js/pimcore/helper.js',
            '/bundles/icecat/js/pimcore/grid/grid.js',
            '/bundles/icecat/js/pimcore/grid/icgridfilters.js',
            '/bundles/icecat/js/pimcore/ice-cat-screen/screen.js',
            '/bundles/icecat/js/pimcore/ice-cat-screen/tabPanels.js',
            '/bundles/icecat/js/pimcore/ice-cat-panel/loginPanel.js',
            '/bundles/icecat/js/pimcore/ice-cat-panel/uploadFilePanel.js',
            '/bundles/icecat/js/pimcore/ice-cat-panel/importGridPanel.js',
            // '/bundles/icecat/js/pimcore/ice-cat-panel/logGridPanel.js',
            '/bundles/icecat/js/pimcore/ice-cat-panel/unfetchedProductGrid.js',
            '/bundles/icecat/js/pimcore/ice-cat-panel/applicationLogGridPanel.js',
            '/bundles/icecat/js/pimcore/ice-cat-panel/objectGridPanel.js',
            '/bundles/icecat/js/pimcore/ice-cat-panel/runningProcessesPanel.js',
            '/bundles/icecat/js/pimcore/progress-bar/activeProcesses.js',
            '/bundles/icecat/js/pimcore/progress-bar/activeCreationProcesses.js',
            '/bundles/icecat/js/pimcore/override/classificationStore.js'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCssPaths()
    {
        return [
            '/bundles/icecat/css/icecat.css'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getInstaller()
    {
        return $this->container->get(\IceCatBundle\InstallClass::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function getComposerPackageName(): string
    {
        return 'icecat/icecat-integration';
    }
}
