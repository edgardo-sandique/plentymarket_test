<?php //strict

namespace PayPal\Helper;

use Plenty\Plugin\Application;
use Plenty\Plugin\ConfigRepository;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentOrderRelationRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Payment\Method\Models\PaymentMethod;
use Plenty\Modules\Payment\Models\PaymentProperty;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Helper\Services\WebstoreHelper;

use PayPal\Services\SessionStorageService;

/**
 * Class PaymentHelper
 * @package PayPal\Helper
 */
class PaymentHelper
{
      /**
       * @var Application
       */
      private $app;

      /**
       * @var WebstoreHelper
       */
      private $webstoreHelper;

      /**
       * @var PaymentMethodRepositoryContract
       */
      private $paymentMethodRepository;

      /**
       * @var ConfigRepository
       */
      private $config;

      /**
       * @var SessionStorageService
       */
      private $sessionService;

      /**
       * @var PaymentOrderRelationRepositoryContract
       */
      private $paymentOrderRelationRepo;

      /**
       * @var PaymentProperty
       */
      private $paymentProperty;

      /**
       * @var PaymentRepositoryContract
       */
      private $paymentRepo;

      /**
       * @var Payment
       */
      private $payment;

      /**
       * @var OrderRepositoryContract
       */
      private $orderRepo;

      /**
       * @var array
       */
      private $statusMap = array();

      /**
       * PaymentHelper constructor.
       *
       * @param Application $app
       * @param WebstoreHelper $webstoreHelper
       * @param PaymentMethodRepositoryContract $paymentMethodRepository
       * @param PaymentRepositoryContract $paymentRepo
       * @param PaymentOrderRelationRepositoryContract $paymentOrderRelationRepo
       * @param ConfigRepository $config
       * @param SessionStorageService $sessionService
       * @param Payment $payment
       * @param PaymentProperty $paymentProperty
       * @param OrderRepositoryContract $orderRepo
       */
      public function __construct(Application $app, PaymentMethodRepositoryContract $paymentMethodRepository, PaymentRepositoryContract $paymentRepo,
                                  PaymentOrderRelationRepositoryContract $paymentOrderRelationRepo,   ConfigRepository $config,
                                  SessionStorageService $sessionService,      Payment $payment,       PaymentProperty $paymentProperty,
                                  OrderRepositoryContract $orderRepo,         WebstoreHelper $webstoreHelper)
      {
            $this->app                          = $app;
            $this->webstoreHelper               = $webstoreHelper;
            $this->config                       = $config;
            $this->sessionService               = $sessionService;
            $this->paymentMethodRepository      = $paymentMethodRepository;
            $this->paymentOrderRelationRepo     = $paymentOrderRelationRepo;
            $this->paymentRepo                  = $paymentRepo;
            $this->paymentProperty              = $paymentProperty;
            $this->orderRepo                    = $orderRepo;
            $this->payment                      = $payment;
            $this->statusMap                    = array();
      }

      /**
       * Create the ID of the payment method if it doesn't exist yet
       */
      public function createMopIfNotExists()
      {

            // Check whether the ID of the PayPal payment method has been created
            if($this->getPayPalMopId() == 'no_paymentmethod_found')
            {
                  $paymentMethodData = array( 'pluginKey' => 'plentyPayPal',
                                              'paymentKey' => 'PAYPAL',
                                              'name' => 'PayPal');

                  $this->paymentMethodRepository->createPaymentMethod($paymentMethodData);
            }

            // Check whether the ID of the PayPal Express payment method has been created
            if($this->getPayPalExpressMopId() == 'no_paymentmethod_found')
            {
                  $paymentMethodData = array( 'pluginKey'   => 'plentyPayPal',
                                              'paymentKey'  => 'PAYPALEXPRESS',
                                              'name'        => 'PayPalExpress');

                  $this->paymentMethodRepository->createPaymentMethod($paymentMethodData);
            }
      }

      /**
       * Get the ID of the PayPal payment method
       *
       * @return mixed
       */
      public function getPayPalMopId()
      {
            // List all payment methods for the given plugin
            $paymentMethods = $this->paymentMethodRepository->allForPlugin('plentyPayPal');

            if( !is_null($paymentMethods) )
            {
                  foreach($paymentMethods as $paymentMethod)
                  {
                        if($paymentMethod->paymentKey == 'PAYPAL')
                        {
                              return $paymentMethod->id;
                        }
                  }
            }

            return 'no_paymentmethod_found';
      }

      /**
       * Get the ID of the PayPal Express payment method
       *
       * @return mixed
       */
      public function getPayPalExpressMopId()
      {
            // List all payment methods for the given plugin
            $paymentMethods = $this->paymentMethodRepository->allForPlugin('plentyPayPal');

            if( !is_null($paymentMethods) )
            {
                  foreach($paymentMethods as $paymentMethod)
                  {
                        if($paymentMethod->paymentKey == 'PAYPALEXPRESS')
                        {
                              return $paymentMethod->id;
                        }
                  }
            }

            return 'no_paymentmethod_found';
      }

      /**
       * Get the REST cancellation URL
       *
       * @return string
       */
      public function getRestCancelURL()
      {
            $webstoreConfig = $this->webstoreHelper->getCurrentWebstoreConfiguration();

            if(is_null($webstoreConfig))
            {
                  return 'error';
            }

            $domain = $webstoreConfig->domainSsl;

            return $domain.'/plentyPayPal/payPalCheckoutCancel';
      }

      /**
       * Get the REST success URL
       *
       * @return string
       */
      public function getRestSuccessURL()
      {
            $webstoreConfig = $this->webstoreHelper->getCurrentWebstoreConfiguration();

            if(is_null($webstoreConfig))
            {
                  return 'error';
            }

            $domain = $webstoreConfig->domainSsl;

            return $domain.'/plentyPayPal/payPalCheckoutSuccess';
      }

      /**
       * Set the PayPal Pay ID
       *
       * @param mixed $value
       */
      public function setPayPalPayID($value)
      {
            $this->sessionService->setSessionValue('PayPalPayId', $value);
      }

      /**
       * Get the PayPal Pay ID
       *
       * @return mixed
       */
      public function getPayPalPayID()
      {
            return $this->sessionService->getSessionValue('PayPalPayId');
      }

      /**
       * Set the PayPal Payer ID
       *
       * @param mixed $value
       */
      public function setPayPalPayerID($value)
      {
            $this->sessionService->setSessionValue('PayPalPayerId', $value);
      }

      /**
       * Get the PayPal Payer ID
       *
       * @return mixed
       */
      public function getPayPalPayerID()
      {
            return $this->sessionService->getSessionValue('PayPalPayerId');
      }

      /**
       * Create a payment in plentymarkets from the JSON data
       *
       * @param string $json
       * @return Payment
       */
      public function createPlentyPaymentFromJson(string $json)
      {
            $payPalPayment = json_decode($json);

            /** @var Payment $payment */
            $payment = clone $this->payment;

            // Set the payment data
            $payment->mopId           = (int)$this->getPayPalMopId();
            $payment->transactionType = 2;
            $payment->status          = $this->mapStatus($payPalPayment->status);
            $payment->currency        = $payPalPayment->currency;
            $payment->amount          = $payPalPayment->amount;
            $payment->entryDate       = $payPalPayment->entryDate;

            /** @var PaymentProperty $paymentProp1 */
            $paymentProp1 = clone $this->paymentProperty;

            /** @var PaymentProperty $paymentProp2 */
            $paymentProp2 = clone $this->paymentProperty;

            // Set the payment properties
            $paymentProp1->typeId   = PaymentProperty::TYPE_BOOKING_TEXT;
            $paymentProp1->value    = 'PayPalPayID: '.(string)$payPalPayment->bookingText;

            // Set the payment properties
            $paymentProp2->typeId   = PaymentProperty::TYPE_ORIGIN;
            $originConstants        = $this->paymentRepo->getOriginConstants();

            if(!is_null($originConstants) && is_array($originConstants))
            {
                  $paymentProp2->value = (string)$originConstants['plugin'];
            }

            /** @var PaymentProperty[] $paymentProps */
            $paymentProps = array(  $paymentProp1,
                                    $paymentProp2     );

            // Add the payment properties to the payment
            $payment->property = $paymentProps;

            $payment = $this->paymentRepo->createPayment($payment);

            return $payment;
      }

      /**
       * Assign the payment to an order in plentymarkets
       *
       * @param Payment $payment
       * @param int $orderId
       */
      public function assignPlentyPaymentToPlentyOrder(Payment $payment, int $orderId)
      {
            // Get the order by the given order ID
            $order = $this->orderRepo->findOrderById($orderId);

            // Check whether the order truly exists in plentymarkets
            if(!is_null($order) && $order instanceof Order)
            {
                  // Assign the given payment to the given order
                  $this->paymentOrderRelationRepo->createOrderRelation($payment, $order);
            }
      }

      /**
       * Map the PayPal payment status to the plentymarkets payment status
       *
       * @param string $status
       * @return int
       *
       */
      public function mapStatus(string $status)
      {
            if(!is_array($this->statusMap) || count($this->statusMap) <= 0)
            {
                  $statusConstants = $this->paymentRepo->getStatusConstants();

                  if(!is_null($statusConstants) && is_array($statusConstants))
                  {
                        $this->statusMap['created']               = $statusConstants['captured'];
                        $this->statusMap['approved']              = $statusConstants['approved'];
                        $this->statusMap['failed']                = $statusConstants['refused'];
                        $this->statusMap['partially_completed']   = $statusConstants['partially_captured'];
                        $this->statusMap['completed']             = $statusConstants['captured'];
                        $this->statusMap['in_progress']           = $statusConstants['awaiting_approval'];
                        $this->statusMap['pending']               = $statusConstants['awaiting_approval'];
                        $this->statusMap['refunded']              = $statusConstants['refunded'];
                        $this->statusMap['denied']                = $statusConstants['refused'];
                  }
            }

            return (int)$this->statusMap[$status];
      }
}