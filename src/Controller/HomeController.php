<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Config\Definition\Exception\Exception;

class HomeController extends Controller
{
    /**
     * @Route("/", name="home")
     */
    public function index()
    {

        $APIKEY = "6E8DE1F0-CAAB-43EA-86BA-4799L42C2069"; // Registre aquí su apiKey
        $SECRETKEY = "20bad08dda5c13b497091fcb0bdc30d936c19c53"; // Registre aquí su secretKey
        $APIURL = "https://www.flow.tuxpan.com/api"; // Producción EndPoint o Sandbox EndPoint
        $BASEURL = "https://www.micomercio.cl/apiFlow"; //Registre aquí la URL base en su página donde instalará el cliente


     //Para datos opcionales campo "optional" prepara un arreglo JSON
$optional = array(
	"rut" => "9999999-9",
	"otroDato" => "otroDato"
);
$optional = json_encode($optional);
//Prepara el arreglo de datos
$params = array(
	"commerceOrder" => rand(1100,2000),
	"subject" => "Pago de prueba",
	"currency" => "CLP",
	"amount" => 5000,
	"email" => "joscri2698@gmail.com",
	"paymentMethod" => 9,
	"urlConfirmation" => $BASEURL . "/examples/payments/confirm.php",
	"urlReturn" => $BASEURL ."/examples/payments/result.php",
	"optional" => $optional
);
//Define el metodo a usar
$serviceName = "payment/create";
try {
	// Instancia la clase FlowApi
	//$flowApi = new FlowApi;
	// Ejecuta el servicio
	$response = $this->send($serviceName, $params,"POST");
	//Prepara url para redireccionar el browser del pagador
	$redirect = $response["url"] . "?token=" . $response["token"];
	//header("location:$redirect");
  return $this->redirect($redirect);
} catch (Exception $e) {
	echo $e->getCode() . " - " . $e->getMessage();
}

     return $this->render('home/index.html.twig', array(
                 'controller_name' => "FLOW",
             ));

    }

    /**
    	 * Funcion que invoca un servicio del Api de Flow
    	 * @param string $service Nombre del servicio a ser invocado
    	 * @param array $params datos a ser enviados
    	 * @param string $method metodo http a utilizar
    	 * @return string en formato JSON
    	 */
    	public function send( $service, $params, $method = "GET") {
    		$method = strtoupper($method);
    		$url = 'https://www.flow.tuxpan.com/api' . "/" . $service;
    		$params = array("apiKey" => "6E8DE1F0-CAAB-43EA-86BA-4799L42C2069") + $params;
    		$data = $this->getPack($params, $method);
    		$sign = $this->sign($params);
    		if($method == "GET") {
    			$response = $this->httpGet($url, $data, $sign);
    		} else {
    			$response = $this->httpPost($url, $data, $sign);
    		}

    		if(isset($response["info"])) {
    			$code = $response["info"]["http_code"];
    			$body = json_decode($response["output"], true);
    			if($code == "200") {
    				return $body;
    			} elseif(in_array($code, array("400", "401"))) {
    				throw new Exception($body["message"], $body["code"]);
    			} else {
    				throw new Exception("Unexpected error occurred. HTTP_CODE: " .$code , $code);
    			}
    		} else {
    			throw new Exception("Unexpected error occurred.");
    		}
    	}

      /**
	 * Funcion que empaqueta los datos de parametros para ser enviados
	 * @param array $params datos a ser empaquetados
	 * @param string $method metodo http a utilizar
	 */
	private function getPack($params, $method) {
		$keys = array_keys($params);
		sort($keys);
		$data = "";
		foreach ($keys as $key) {
			if($method == "GET") {
				$data .= "&" . rawurlencode($key) . "=" . rawurlencode($params[$key]);
			} else {
				$data .= "&" . $key . "=" . $params[$key];
			}
		}
		return substr($data, 1);
	}


	/**
	 * Funcion que firma los parametros
	 * @param string $params Parametros a firmar
	 * @return string de firma
	 */
	private function sign($params) {
		$keys = array_keys($params);
		sort($keys);
		$toSign = "";
		foreach ($keys as $key) {
			$toSign .= "&" . $key . "=" . $params[$key];
		}
		$toSign = substr($toSign, 1);
		if(!function_exists("hash_hmac")) {
			throw new Exception("function hash_hmac not exist", 1);
		}
		return hash_hmac('sha256', $toSign , "20bad08dda5c13b497091fcb0bdc30d936c19c53");
	}

  /**
	 * Funcion que hace el llamado via http GET
	 * @param string $url url a invocar
	 * @param array $data datos a enviar
	 * @param string $sign firma de los datos
	 * @return string en formato JSON
	 */
	private function httpGet($url, $data, $sign) {
		$url = $url . "?" . $data . "&s=" . $sign;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$output = curl_exec($ch);
		if($output === false) {
			$error = curl_error($ch);
			throw new Exception($error, 1);
		}
		$info = curl_getinfo($ch);
		curl_close($ch);
		return array("output" =>$output, "info" => $info);
	}

  /**
	 * Funcion que hace el llamado via http POST
	 * @param string $url url a invocar
	 * @param array $data datos a enviar
	 * @param string $sign firma de los datos
	 * @return string en formato JSON
	 */
	private function httpPost($url, $data, $sign ) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data . "&s=" . $sign);
		$output = curl_exec($ch);
		if($output === false) {
			$error = curl_error($ch);
			throw new Exception($error, 1);
		}
		$info = curl_getinfo($ch);
		curl_close($ch);
		return array("output" =>$output, "info" => $info);
	}

}

?>
