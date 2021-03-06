<?php

namespace Bundle\Site\MarketPlace\Action;

use Bundle\Site\MarketPlace\Service\PackageManager;
use Bundle\Site\MarketPlace\Storage\Entity;
use Bundle\Site\MarketPlace\Storage\Repository\Package;
use Bolt\Storage\EntityManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Package releases action.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Ross Riley <riley.ross@gmail.com>
 */
class PackageReleases extends AbstractAction
{
    /**
     * {@inheritdoc}
     */
    public function execute(Request $request, array $params)
    {
        /** @var UrlGeneratorInterface $urlGen */
        $urlGen = $this->getAppService('url_generator');
        /** @var Session $session */
        $session = $this->getAppService('session');
        /** @var EntityManager $em */
        $em = $this->getAppService('storage');
        /** @var Package $repo */
        $repo = $em->getRepository(Entity\Package::class);
        /** @var Entity\Package $package */
        $package = $repo->findOneBy(['id' => $params['package']]);

        if (!$package) {
            $session->getFlashBag()->add('error', 'There was a problem accessing this package');
            $route = $urlGen->generate('profile');

            return new RedirectResponse($route);
        }

        $versions = ['dev' => [], 'stable' => []];
        /** @var PackageManager $packageManager */
        $packageManager = $this->getAppService('marketplace.manager_package');

        $token = $this->getAppService('config')->get('general/token/github');
        $repo = $em->getRepository(Entity\VersionBuild::class);
        $info = $packageManager->getInfo($package, false, $token);

        $i = 0;
        foreach ($info as $ver) {
            $build = $repo->findOneBy(['package_id' => $package->getId(), 'version' => $ver['version']]);
            if ($build) {
                $ver['build'] = $build;
            }
            $versions[$ver['stability']][] = $ver;
            $i++;
        }
        foreach ($versions as $stability => &$vers) {
            krsort($vers);
        }

        /** @var \Twig_Environment $twig */
        $twig = $this->getAppService('twig');
        $context = [
            'package'  => $package,
            'versions' => $versions,
        ];
        $html = $twig->render('releases.twig', $context);

        return new Response($html);
    }
}
