<?php

namespace Kryn\CmsBundle\Controller;

use Kryn\CmsBundle\Admin\AdminAssets;
use Kryn\CmsBundle\PluginController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * FrontEnd - Page controller
 */
class PageController extends PluginController
{

    /**
     * Build the page and return the Response of Core\Kryn::getResponse().
     *
     * @return Response
     */
    public function handleAction()
    {
        $page = $this->getKrynCore()->getCurrentPage();

        //is link
        if ($page->getType() == 1) {
            $to = $page->getLink();
            if (!$to) {
                throw new \Exception('Redirect failed: ' .
                    sprintf('Current page with title %s has no target link.', $page->getTitle())
                );
            }

            if (intval($to) > 0) {
                return new RedirectResponse($this->getKrynCore()->getNodeUrl($to), 301);
            } else {
                return new RedirectResponse($to, 301);
            }
        }

        if ($this->getKrynCore()->isEditMode()) {
            $adminAssets = new AdminAssets($this->getKrynCore());
            $adminAssets->handleKEditor();
        }

        $pageResponse = $this->getKrynCore()->getPageResponse();
        $pageResponse->setRenderFrontPage(true);
        $pageResponse->renderContent();

        return $pageResponse; //new Response('<body>ho</body>');
    }

    /**
     * Returns a permanent(301) redirectResponse object.
     *
     * @return RedirectResponse
     */
    public function redirectToStartPageAction()
    {
        $qs = $this->getKrynCore()->getRequest()->getQueryString();
        $response = new RedirectResponse($this->getKrynCore()->getRequest()->getBaseUrl()  . ($qs ? '?'.$qs:''), 301);

        return $response;
    }
}
