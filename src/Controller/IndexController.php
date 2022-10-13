<?php
namespace Paw\Controller;

use Paw\AbstractController;

class IndexController extends AbstractController
{
    public function get()
    {
		$listings = $this->getDb()->market_all_listings();
		
        $this->addHeader();
        $this->addTemplate('partials/index.phtml', [
            "LISTINGS" =>  $listings
        ]);
        $this->addFooter();
        return $this->getResponse();
    }
}