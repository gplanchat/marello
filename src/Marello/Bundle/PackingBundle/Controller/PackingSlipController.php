<?php

namespace Marello\Bundle\PackingBundle\Controller;

use Marello\Bundle\OrderBundle\Entity\Order;
use Marello\Bundle\PackingBundle\Entity\PackingSlip;
use Marello\Bundle\ReturnBundle\Entity\ReturnEntity;
use Marello\Bundle\ReturnBundle\Form\Type\ReturnUpdateType;
use Oro\Bundle\SecurityBundle\Annotation as Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Config;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class PackingSlipController extends Controller
{
    /**
     * @Config\Route("/", name="marello_packing_packingslip_index")
     * @Config\Template
     * @Security\AclAncestor("marello_packing_slip_view")
     */
    public function indexAction()
    {
        return ['entity_class' => 'MarelloPackingBundle:PackingSlip'];
    }

    /**
     * @Config\Route("/view/{id}", requirements={"id"="\d+"})
     * @Config\Template
     * @Security\AclAncestor("marello_packing_slip_view")
     *
     * @param PackingSlip $packingSlip
     *
     * @return array
     */
    public function viewAction(PackingSlip $packingSlip)
    {
        return ['entity' => $packingSlip];
    }
}
