<?php
class ModelModuleSamson extends Model {

    public function installDB() {	
        $query = $this->db->query("CREATE TABLE IF NOT EXISTS  " . DB_PREFIX . "samson(
			`id` int(11) NOT NULL primary key AUTO_INCREMENT,
			`percent` int(11),
			`key` text CHARACTER SET utf8 COLLATE utf8_general_ci
			)");;
    }           

    public function getKey() {
		$query = $this->db->query("SELECT `key` FROM " . DB_PREFIX . "samson");
		foreach($query->rows as $result){
			$key = $result['key'];
		}
		return $key;
	}

    public function setKey($key) {
		$this->db->query("UPDATE " . DB_PREFIX . "samson SET `key` = '" . $this->db->escape($key) . "' WHERE `id` = 1");
	}

	public function getPercent() {
		$query = $this->db->query("SELECT `percent` FROM " . DB_PREFIX . "samson");
		foreach($query->rows as $result){
			$key = $result['percent'];
		}
		return $key;
	}

    public function setPercent($percent) {
		$this->db->query("UPDATE " . DB_PREFIX . "samson SET `percent` = '" . $percent . "' WHERE `id` = 1");
	}

    public function getSkuPrice() {
    	$query = $this->db->query("SELECT `sku`, `price`  FROM " . DB_PREFIX . "product");
		
		return $query;
	}

	public function setSkuPrice($sku, $price) {
		$sql2 = NULL;
		if(count($sku) == 1){
			$sql = "UPDATE " . DB_PREFIX . "product SET price = '" . $price[0] . "' WHERE product.sku = '" . $sku[0] . "'";
		} else {
			$sql1 = "UPDATE " . DB_PREFIX . "product, ( SELECT '" . $price[0] . "' AS price, '" . $sku[0] . "' AS sku ";
			for ($i = 1; $i <= count($sku)-1; $i++) {
                  $sql2 .= " UNION SELECT '" . $price[$i] . "', '" . $sku[$i] . "'";
				}
			$sql3 = ") src SET  " . DB_PREFIX . "product.price = src.price WHERE  " . DB_PREFIX . "product.sku = src.sku";
			$sql = $sql1 . $sql2 . $sql3;
		}
		$this->db->query($sql);
	}
}
?>