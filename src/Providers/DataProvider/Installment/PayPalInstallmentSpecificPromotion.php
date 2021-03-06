<?php
/**
 * Created by IntelliJ IDEA.
 * User: jkonopka
 * Date: 12.01.17
 * Time: 09:40
 */

namespace PayPal\Providers\DataProvider\Installment;

use PayPal\Services\PaymentService;
use PayPal\Services\PayPalInstallmentService;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Item\DataLayer\Models\Record;
use Plenty\Plugin\Templates\Twig;

class PayPalInstallmentSpecificPromotion
{
    public function call(   Twig $twig,
                            BasketRepositoryContract $basketRepositoryContract,
                            PayPalInstallmentService $payPalInstallmentService,
                            PaymentService $paymentService,
                            $arg)
    {
        $item = null;
        if(is_array($arg) && array_key_exists(0, $arg))
        {
            /** @var Record $item */
            $item = $arg[0];
        }
        $amount = 0;
        if(is_null($item))
        {
            $basket = $basketRepositoryContract->load();
            $amount = $basket->basketAmount;
        }
        elseif (!is_null($item))
        {
            $amount = $item->getVariationRetailPrice()->getPrice();;
        }

        $qualifyingFinancingOptions = [];

        $paymentService->loadCurrentSettings('paypal_installment');

        if($paymentService->settings['calcFinancing'] == 1)
        {
            /**
             * Load the specific promotion with calculated financing options
             */
            $financingOptions = $payPalInstallmentService->getFinancingOptions($amount);
            if(is_array($financingOptions) && array_key_exists('financing_options', $financingOptions))
            {
                if(is_array($financingOptions['financing_options'][0]) && is_array(($financingOptions['financing_options'][0]['qualifying_financing_options'])))
                {
                    $qualifyingFinancingOptions = $financingOptions['financing_options'][0]['qualifying_financing_options'][0];
                }
            }
        }
        return $twig->render('PayPal::PayPalInstallment.SpecificPromotion', ['amount'=>$amount, 'financingOptions'=>$qualifyingFinancingOptions, 'item'=>$item,'merchantName'=>'Testfirma', 'merchantAddress'=>'Teststraße 1, 34117 Kassel']);
    }

}