<?hh // strict

namespace PayPal\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;

class PayPalRouteServiceProvider extends RouteServiceProvider
{
	public function map(Router $router, LibraryCallContract $libCall):void
	{
		$router->get('test', () ==> {
			$result = $libCall->call('PayPal::preparePayment', ['foo' => 'bar']);
			return $result;
		});

		$router->get('payPalExpressButton', 'PayPal\Controllers\PaymentController@showPPExpressButton');

		//paypal return urls
		$router->get('payPalCheckoutSuccess', 'PayPal\Controllers\PaymentController@ppCheckoutSuccess');
		$router->get('payPalCheckoutCancel', 'PayPal\Controllers\PaymentController@ppCheckoutCancel');

		//trigger prepare payment
		$router->get('preparePayPalPayment', 'PayPal\Controllers\PaymentController@preparePayment');
	}
}