<?php

namespace Marello\Bundle\ProductBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation as Security;

use Marello\Bundle\ProductBundle\Entity\Product;

class ProductController extends Controller
{
    /**
     * @Route(
     *      "/{_format}",
     *      name="marello_product_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format"="html"}
     * )
     * @Security\AclAncestor("marello_product_view")
     * @Template()
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route("/create", name="marello_product_create")
     * @Security\AclAncestor("marello_product_create")
     * @Template("MarelloProductBundle:Product:update.html.twig")
     */
    public function createAction()
    {
        return $this->update(new Product());
    }

    /**
     * @Route("/update/{id}", requirements={"id"="\d+"}, name="marello_product_update")
     * @Security\AclAncestor("marello_product_update")
     * @Template()
     *
     * @param Product $product
     *
     * @return array
     */
    public function updateAction(Product $product)
    {
        return $this->update($product);
    }

    /**
     * @param Product $product
     *
     * @return array
     */
    protected function update(Product $product)
    {
        $handler = $this->get('marello_product.product_form.handler');

        if ($handler->process($product)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('marello.product.messages.success.product.saved')
            );

            return $this->get('oro_ui.router')->redirectAfterSave(
                [
                    'route'      => 'marello_product_update',
                    'parameters' => [
                        'id'                      => $product->getId(),
                        '_enableContentProviders' => 'mainMenu'
                    ]
                ],
                [
                    'route'      => 'marello_product_view',
                    'parameters' => [
                        'id'                      => $product->getId(),
                        '_enableContentProviders' => 'mainMenu'
                    ]
                ],
                $product
            );
        }

        return [
            'entity' => $product,
            'form'   => $handler->getFormView(),
        ];
    }

    /**
     * @Route("/view/{id}", requirements={"id"="\d+"}, name="marello_product_view")
     * @Security\AclAncestor("marello_product_view")
     * @Template
     *
     * @param Product $product
     *
     * @return array
     */
    public function viewAction(Product $product)
    {
        return [
            'entity' => $product,
        ];
    }

    /**
     * @Route("/widget/info/{id}", name="marello_product_widget_info", requirements={"id"="\d+"})
     * @Security\AclAncestor("marello_product_view")
     * @Template
     *
     * @param Product $product
     *
     * @return array
     */
    public function infoAction(Product $product)
    {
        return [
            'product' => $product
        ];
    }

    /**
     * @Route("/widget/price/{id}", name="marello_product_widget_price", requirements={"id"="\d+"})
     * @Security\AclAncestor("marello_product_view")
     * @Template
     *
     * @param Product $product
     *
     * @return array
     */
    public function priceAction(Product $product)
    {
        return [
            'product' => $product
        ];
    }
}
