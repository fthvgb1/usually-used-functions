<?php
/**
 * Created by PhpStorm.
 * User: xing
 * Date: 18-11-28
 * Time: 下午10:47
 */


/**
 * 多线程处理
 * Class MultipleCurl
 */
class MultipleCurl
{

    public static $options = [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_TIMEOUT => 15,
    ];
    protected static $instance = null;
    protected $mh;
    protected $handle = null;
    protected $maxThread = 30;
    protected $chs = [];

    public function __construct()
    {
        $this->mh = curl_multi_init();
    }

    public static function getInstance()
    {
        return self::$instance ? self::$instance : self::$instance = new self();
    }

    /**
     * 设置最大线程数
     * @param int $maxThread
     * @return $this
     */
    public function setMaxThread(int $maxThread)
    {
        $this->maxThread = $maxThread;
        return $this;
    }

    public function __destruct()
    {
        curl_multi_close($this->mh);
    }

    public function limitGet($url = [])
    {
        if (count($url) > $this->maxThread) {
            $chunks = array_chunk($url, $this->maxThread, true);
            $res = [];
            foreach ($chunks as $chunk) {
                $this->chs = [];
                $res[] = $this->get($chunk);
            }
            return array_values($res);
        } else {
            return $this->get($url);
        }
    }

    /**
     * 获取网页
     * @param array $urls [ 'url','url'=>callable($ch),'url'=>[curl_setopt_field=>option]]
     * @param array $options curl_setopt_array
     * @return array
     */
    public function get($urls = [], $options = [])
    {
        foreach ($urls as $i => $url) {
            if (strpos($i, 'http') !== false) {
                $this->chs[$i] = curl_init($i);
                if (is_callable($url)) {
                    call_user_func_array($url, [&$this->chs[$i]]);
                } elseif (is_array($url)) {
                    curl_setopt_array($this->chs[$i], $url);
                }
            } else {
                $this->chs[$i] = curl_init($url);
                curl_setopt_array($this->chs[$i], self::$options);
            }

            if ($options) {
                curl_setopt_array($this->chs[$url], $options);
            }
            curl_multi_add_handle($this->mh, $this->chs[$i]);
        }
        return $this->run();
    }

    protected function run()
    {

        do {
            $mrc = curl_multi_exec($this->mh, $run);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($run && $mrc == CURLM_OK) {
            if (curl_multi_select($this->mh) != -1) {
                do {
                    $mrc = curl_multi_exec($this->mh, $run);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        $res = [];

        foreach ($this->chs as $k => $v) {
            if (isset($this->handle[$k]) && is_callable($this->handle[$k])) {
                call_user_func_array($this->handle[$k], [$v]);
            } else {
                $res[$k] = curl_multi_getcontent($v);
            }
            curl_multi_remove_handle($this->mh, $v);
        }
        return $res;
    }

    public function limitPostData($url_data = [], $options = [])
    {
        if (count($url_data) > $this->maxThread) {
            $chunks = array_chunk($url_data, $this->maxThread, true);
            $res = [];
            foreach ($chunks as $chunk) {
                $this->chs = [];
                $res[] = $this->postData($chunk, $options);
            }
            return array_values($res);
        } else {
            return $this->postData($url_data, $options);
        }
    }

    /**
     * post提交表单
     * @param array $url_data data为URL-encoded 字符串时(http_build_query(array))，数据会被编码成 application/x-www-form-urlencoded,
     * data为array时会把数据编码成 multipart/form-data
     * [
     *  ['url'=>'url','data'=>['postfield'=>'data'],'set_handle'=>callable($ch),'callback'=>callable($ch)],
     *  'url'=>['postfield'=>'data','file'=>new CURLFile(absolute_path,[mineType],[postName])],
     *
     * ]
     * @param array $options curl_setopt_array
     * @return array
     */
    public function postData($url_data = [], $options = [])
    {
        $i = 0;
        foreach ($url_data as $url => $data) {
            if (strpos($url, 'http') !== false) {
                $this->chs[$url] = curl_init($url);
                if (is_callable($data)) {
                    call_user_func_array($data, [&$this->chs[$url]]);
                } else {
                    curl_setopt_array($this->chs[$url], [
                        CURLOPT_POST => 1,
                        CURLOPT_POSTFIELDS => $data
                    ]);
                }
                if ($options) {
                    curl_setopt_array($this->chs[$url], $options);
                }
                curl_multi_add_handle($this->mh, $this->chs[$url]);
            } else {
                if (isset($data['url']) && $data['url']) {
                    $u = $i;
                    $this->chs[$u] = curl_init($data['url']);
                    if (isset($data['callback']) && is_callable($data['callback'])) {
                        $this->handle[$u] = $data['callback'];
                    }
                    if (isset($data['set_handle']) && is_callable($data['set_handle'])) {
                        call_user_func_array($data['set_handle'], [&$this->chs[$u]]);
                    } else {
                        curl_setopt_array($this->chs[$u], [
                            CURLOPT_POST => 1,
                            CURLOPT_POSTFIELDS => $data['data'] ?? []
                        ]);
                    }

                    if ($options) {
                        curl_setopt_array($this->chs[$u], $options);
                    }
                    curl_multi_add_handle($this->mh, $this->chs[$u]);
                    ++$i;
                }
            }
        }
        return $this->run();
    }

    public function limitDownload($url_path = [], $options = [])
    {
        if (count($url_path) > $this->maxThread) {
            $chunks = array_chunk($url_path, $this->maxThread, true);
            foreach ($chunks as $chunk) {
                $this->chs = [];
                $this->download($chunk, $options);
            }
        } else {
            $this->download($url_path, $options);
        }
    }

    /**
     * 下载文件
     * @param array $url_path ['url'=>'save_path','url'=>callable($binContent,$ch)]
     * @param array $options curl_setopt_array
     */
    public function download($url_path = [], $options = [])
    {
        foreach ($url_path as $url => $path) {
            $this->chs[$url] = curl_init($url);
            curl_setopt_array($this->chs[$url], $options ?: self::$options);
            if ($options) {
                curl_setopt_array($this->chs[$url], $options);
            }
            curl_multi_add_handle($this->mh, $this->chs[$url]);
            $this->handle[$url] = function ($ch) use ($path) {
                $content = curl_multi_getcontent($ch);
                if (is_callable($path)) {
                    call_user_func_array($path, [$content, $ch]);
                } else {
                    $fp = fopen($path, 'wb');
                    fwrite($fp, $content);
                    fclose($fp);
                }

            };

        }
        $this->run();
    }

}