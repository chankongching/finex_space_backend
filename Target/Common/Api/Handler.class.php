<?php
/**
 * Session handler class that relies on Predis\Client to store PHP's sessions
 * data into one or multiple Redis servers.
 *
 * This class is mostly intended for PHP 5.4 but it can be used under PHP 5.3
 * provided that a polyfill for `SessionHandlerInterface` is defined by either
 * you or an external package such as `symfony/http-foundation`.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
namespace Common\Api;

class Handler implements \SessionHandlerInterface
{
    protected $client;
    protected $ttl;
    
    protected $prefix='admin_sess_';
    /**
     * @param resource    $client  initialized client instance.
     * @param array       $options Session handler options.
     */
    public function __construct($client, array $options = array())
    {
        $this->client = $client;
        if (isset($options['gc_maxlifetime'])) {
            $this->ttl = (int) $options['gc_maxlifetime'];
        } else {
            $this->ttl = ini_get('session.gc_maxlifetime');
        }
    }

    /**
     * Registers this instance as the current session handler.
     */
    public function register()
    {
        if (PHP_VERSION_ID >= 50400) {
            session_set_save_handler($this, true);
        } else {
            session_set_save_handler(
                    array($this, 'open'),
                    array($this, 'close'),
                    array($this, 'read'),
                    array($this, 'write'),
                    array($this, 'destroy'),
                    array($this, 'gc')
                    );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function open($save_path, $session_id)
    {
        // NOOP
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        // NOOP
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime)
    {
        // NOOP
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($session_id)
    {  
        if ($data = $this->client->get($this->prefix.$session_id)) {
            return $data;
        }
        return '';
    }
    /**
     * {@inheritdoc}
     */
    public function write($session_id, $session_data=null)
    {   
        // 默认页面载入不写入空值
        if(!empty($session_data))
        {
            $this->client->setex($this->prefix.$session_id, $this->ttl, $session_data);
            session_commit();
            return true;
        }
        //unset值 调用redis del删除 ; 避免保留空值redis
//         $this->destroy($session_id);
//         return true ;
    }
    /**
     * {@inheritdoc}
     */
    public function destroy($session_id)
    {
        $this->client->del($this->prefix.$session_id);
        return true;
    }

    /**
     * Returns the underlying client instance.
     * @return resource ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }
    /**
     * Returns the session max lifetime value.
     * @return int
     */
    public function getMaxLifeTime()
    {
        return $this->ttl;
    }
    
    /**
      * @method  析构函数对象销毁执行
    */
    public function __destruct()
    {
        session_write_close();
    }
    
}