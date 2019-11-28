<?php

namespace Bundle\Site\MarketPlace\Action;

use Bundle\Site\MarketPlace\Service\PackageManager;
use Bundle\Site\MarketPlace\Storage\Entity;
use Bundle\Site\MarketPlace\Storage\Repository;
use Bolt\Storage\EntityManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Package information action.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Ross Riley <riley.ross@gmail.com>
 */
class PackageInfo extends AbstractAction
{
    /**
     * {@inheritdoc}
     */
    public function execute(Request $request, array $params)
    {
        $p = $request->get('package');
        $bolt = $request->get('bolt');

        /** @var EntityManager $em */
        $em = $this->getAppService('storage');
        /** @var Repository\Package $packageRepo */
        $packageRepo = $em->getRepository(Entity\Package::class);
        /** @var Entity\Package $package */
        $package = array_reverse($packageRepo->findOneBy(['approved' => true, 'name' => $p]));

        if (!$package) {
            return new JsonResponse(['package' => false, 'version' => false]);
        }

        /** @var PackageManager $packageManager */
        $packageManager = $this->getAppService('marketplace.manager_package');
        $allVersions = $packageManager->getInfo($package, $bolt);

        $buildRepo = $em->getRepository(Entity\VersionBuild::class);
        foreach ($allVersions as &$version) {
            /** @var Entity\VersionBuild $build */
            $build = $buildRepo->findOneBy(['package_id' => $package->getId(), 'version' => $version['version']]);
            if ($build) {
                $version['buildStatus'] = $build->getTestStatus();
            } else {
                $version['buildStatus'] = 'untested';
            }
        }

        $response = new JsonResponse(['package' => $package->serialize(), 'version' => $allVersions]);
        $response->setCallback($request->get('callback'));

        return $response;
    }
}
