<?php 

/**
 * Name.com PHP API Class
 * Class that handles all name.com Partnership
 *
 * @author rama@networks.co.id
 */
class NameDotComApi
{
	private $session_token;
	public $url = 'https://api.dev.name.com';
	public $data;

	public function __construct($username, $api_token)
	{
		$post = array('username' => $username, 'api_token' => $api_token);
		$request = Requests::post($this->url . '/api/login', array(), json_encode($post));
		$data = json_decode($request->body, TRUE);
		$this->session_token = $data['session_token'];
	}

    /**
     * Add Price
     * Append the price when we do check availibility
     *
     * @var string $tld the tld (.com,.etc)
     * @var string $price how much we add price (1.32)
     * @return bool false or true
     * @author rama@networks.co.id
     */
	public function addPrice($tld, $price)
	{
		if (!isset($this->data['price_add'][$tld])) 
		{
			$this->data['price_add'][$tld] = $price; 
			return true;
		} 
		else 
		{
			return false;
		}	
	}

    /**
     * Get Domain List
     * get all the domain in root account
     *
     * @return array $domain
     * @author rama@networks.co.id
     */
	public function getDomainList()
	{
		$request = Requests::get($this->url . '/api/domain/list', array('Api-Session-Token' => $this->session_token));
		$data = json_decode($request->body, TRUE);	
		return $data['domains'];
	}

    /**
     * Check Domain Availibility
     * Check the domain availibility and the current price or the new appened price
     *
     * @var string $keyword
     * @return array $domains
     * @author rama@networks.co.id
     */
	public function checkDomain($keyword)
	{	
		$post = array('keyword' => $keyword, 'tlds' => array('com','org','me'), 'services' => array('availability'));
		$request = Requests::post($this->url . '/api/domain/check', array(), json_encode($post));
		$data = json_decode($request->body, TRUE);
		return array_map(array($this, "__processPrice"), $data['domains']);
	}

    /**
     * Process the pricing
     * used in check availiblity to add the current price, using map array
     * returning the array with the new appended price
     *
     * @var array $domains
     * @return array $domain
     * @author rama@networks.co.id
     */
	private function __processPrice($domains)
	{
		$rupiah = $this->__getRupiah();
		if (!empty($this->data['price_add'][$domains['tld']])) {
			$domains['price'] = (float)$domains['price'] + (float)$this->data['price_add'][$domains['tld']];
		}

		$domains['price'] = (float)$domains['price'] * (float)$rupiah['usd']['buy'];
		return number_format($domains['price'], 2);
	}

    /**
     * Optional Get Rupiah
     * Get rupiah from klikbca
     *
     * @return array $return
     * @author rama@networks.co.id
     */
	private function __getRupiah()
	{
		$request = Requests::get('http://www.bca.co.id/id/biaya-limit/kurs_counter_bca/kurs_counter_bca_landing.jsp');
		
		preg_match('(\d\d\40\w+\40\d\d\d\d\40/\40\d\d:\d\d\40\w+)', $request->body, $data['date']);
		preg_match_all('/\<td style="text-align:right;"\>(\d+.\d+)\<\/td\>/', $request->body, $data['curr']);

		$return['date'] = $data['date'][0];
		$return['usd']['sell'] = $data['curr'][1][0];
		$return['usd']['buy'] = $data['curr'][1][1];

		return $return;
	}
}
?>