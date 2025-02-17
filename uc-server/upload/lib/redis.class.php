<?php

/**
 * [UCenter] (C)2001-2099 Discuz! Team
 * This is NOT a freeware, use is subject to license terms
 * https://license.discuz.vip
 */

class ucserver_redis {

	public $enable;
	public $obj;

	public function env() {
		return extension_loaded('redis');
	}

	public function info($sections) {
		return $this->obj->info($sections);
	}

	private function _key($str) {
		if(is_array($str)) {
			foreach($str as &$val) {
				$val = UC_REDIS_KEYPREFIX.$val;
			}
		} else {
			$str = UC_REDIS_KEYPREFIX.$str;
		}
		return $str;
	}

	public function init() {
		if(!$this->env()) {
			$this->enable = false;
			return;
		}

		if(defined('UC_REDIS_HOST') && UC_REDIS_HOST != '') {
			try {
				$this->obj = new Redis();
				if(UC_REDIS_CONNECT) {
					$connect = @$this->obj->pconnect(UC_REDIS_HOST, UC_REDIS_PORT);
				} else {
					$connect = @$this->obj->connect(UC_REDIS_HOST, UC_REDIS_PORT);
				}
			} catch (RedisException $e) {

			}
			$this->enable = (bool)$connect;
			if($this->enable) {
				if(UC_REDIS_PASS) {
					$this->obj->auth(UC_REDIS_PASS);
				}
				@$this->obj->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);
				$this->select(UC_REDIS_DB ?? 0);
			}
		}
	}

	public function feature($feature) {
		switch($feature) {
			case 'set':
			case 'hash':
			case 'sortedset':
			case 'pipeline':
				return true;
			case 'eval':
				$ret = $this->obj->eval('return 1');
				return ($ret === 1);
			case 'cluster':
				$ret = $this->obj->info('cluster');
				return ($ret['cluster_enabled'] === 1);
			default:
				return false;
		}
	}

	public static function &instance() {
		static $object;
		if(empty($object)) {
			$object = new ucserver_redis();
			$object->init();
		}
		return $object;
	}

	public function get($key) {
		if(is_array($key)) {
			return $this->getMulti($key);
		}
		return $this->_try_deserialize($this->obj->get($this->_key($key)));
	}

	public function getMulti($keys) {
		$keys = array_map(function($key) {
			return $this->_key($key);
		}, $keys);
		$result = $this->obj->mGet($keys);
		$newresult = [];
		$index = 0;
		foreach($keys as $key) {
			if($result[$index] !== false) {
				$newresult[$key] = $this->_try_deserialize($result[$index]);
			}
			$index++;
		}
		unset($result);
		return $newresult;
	}

	public function select($db = 0) {
		return $this->obj->select($db);
	}

	public function set($key, $value, $ttl = 0) {
		if(is_array($value)) {
			$value = json_encode($value);
		}
		if($ttl) {
			return $this->obj->setex($this->_key($key), $ttl, $value);
		} else {
			return $this->obj->set($this->_key($key), $value);
		}
	}

	public function add($key, $value, $ttl = 0) {
		if($ttl > 0) return $this->obj->set($this->_key($key), $value, ['nx', 'ex' => $ttl]);
		return $this->obj->setnx($this->_key($key), $value);
	}

	public function rm($key) {
		return $this->obj->del($this->_key($key));
	}

	public function setMulti($arr, $ttl = 0) {
		if(!is_array($arr)) {
			return FALSE;
		}
		foreach($arr as $key => $v) {
			$this->set($this->_key($key), $v, $ttl);
		}
		return TRUE;
	}

	public function inc($key, $step = 1) {
		return $this->obj->incr($this->_key($key), $step);
	}

	public function incex($key, $step = 1) {
		$script = "if redis.call('exists', ARGV[1]) == 1 then return redis.call('incrby', ARGV[1], ARGV[2]) end";
		return $this->evalscript($script, [$this->_key($key), $step]);
	}

	public function dec($key, $step = 1) {
		return $this->obj->decr($this->_key($key), $step);
	}

	public function getSet($key, $value) {
		return $this->obj->getSet($this->_key($key), $value);
	}

	public function sadd($key, $value) {
		return $this->obj->sAdd($this->_key($key), $value);
	}

	public function srem($key, $value) {
		return $this->obj->sRem($this->_key($key), $value);
	}

	public function smembers($key) {
		return $this->obj->sMembers($this->_key($key));
	}

	public function sismember($key, $member) {
		return $this->obj->sIsMember($this->_key($key), $member);
	}

	public function keys($key) {
		return $this->obj->keys($key);
	}

	public function expire($key, $second) {
		return $this->obj->expire($this->_key($key), $second);
	}

	public function scard($key) {
		return $this->obj->sCard($this->_key($key));
	}

	public function hSet($key, $field, $value) {
		return $this->obj->hSet($this->_key($key), $field, $value);
	}

	public function hmset($key, $value) {
		return $this->obj->hMSet($this->_key($key), $value);
	}

	public function hDel($key, $field) {
		return $this->obj->hDel($this->_key($key), $field);
	}

	public function hLen($key) {
		return $this->obj->hLen($this->_key($key));
	}

	public function hVals($key) {
		return $this->obj->hVals($this->_key($key));
	}

	public function hIncrBy($key, $field, $incr) {
		return $this->obj->hIncrBy($this->_key($key), $field, $incr);
	}

	public function hgetall($key) {
		return $this->obj->hGetAll($this->_key($key));
	}

	public function hget($key, $field) {
		return $this->obj->hGet($this->_key($key), $field);
	}

	public function hexists($key, $field) {
		return $this->obj->hExists($this->_key($key), $field);
	}

	public function evalscript($script, $argv) {
		return $this->obj->eval($script, $argv);
	}

	public function evalsha($sha, $argv) {
		return $this->obj->evalSha($sha, $argv);
	}

	public function loadscript($script) {
		return $this->obj->script('load', $script);
	}

	public function scriptexists($sha) {
		$r = $this->obj->script('exists', $sha);
		return $r[0];
	}

	public function zadd($key, $member, $score) {
		return $this->obj->zAdd($this->_key($key), $score, $member);
	}

	public function zrem($key, $member) {
		return $this->obj->zRem($this->_key($key), $member);
	}

	public function zscore($key, $member) {
		return $this->obj->zScore($this->_key($key), $member);
	}

	public function zcard($key) {
		return $this->obj->zCard($this->_key($key));
	}

	public function zrevrange($key, $start, $end, $withscore = false) {
		return $this->obj->zRevRange($this->_key($key), $start, $end, $withscore);
	}

	public function zincrby($key, $member, $value) {
		return $this->obj->zIncrBy($this->_key($key), $value, $member);
	}

	public function sort($key, $opt) {
		return $this->obj->sort($this->_key($key), $opt);
	}

	public function exists($key) {
		return $this->obj->exists($this->_key($key));
	}

	public function clear($key = '') {
		if($key) {
			$keys = $this->keys($this->_key($key).'*');
			$n = 0;
			foreach($keys as $k) {
				$this->rm($k);
				$n++;
			}
			return $n;
		}
		return $this->obj->flushDb();
	}

	public function pipeline() {
		return $this->obj->multi(Redis::PIPELINE);
	}

	public function commit() {
		return $this->obj->exec();
	}

	public function discard() {
		return $this->obj->discard();
	}

	private function _try_deserialize($data) {
		try {
			$ret = json_decode($data, true);
			if($ret === FALSE) {
				return $data;
			}
			return $ret;
		} catch (Exception $e) {
		}
		return $data;
	}

}

