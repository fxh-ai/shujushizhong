<?php

namespace app\admin\command;

use app\admin\model\AuthRule;
use ReflectionClass;
use ReflectionMethod;
use think\Cache;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\Exception;
use think\Loader;
use think\Db;

class Menu extends Command
{
    protected $model = null;

    protected function configure()
    {
        $this
            ->setName('menu')
            ->addOption('controller', 'c', Option::VALUE_REQUIRED | Option::VALUE_IS_ARRAY, 'controller name,use \'all-controller\' when build all menu', null)
            ->addOption('delete', 'd', Option::VALUE_OPTIONAL, 'delete the specified menu', '')
            ->addOption('force', 'f', Option::VALUE_OPTIONAL, 'force delete menu,without tips', null)
            ->addOption('equal', 'e', Option::VALUE_OPTIONAL, 'the controller must be equal', null)
            ->setDescription('Build auth menu from controller');
        //要执行的controller必须一样，不适用模糊查询
    }

    protected function execute(Input $input, Output $output)
    {
        // 确保数据库连接使用UTF-8编码
        Db::execute("SET NAMES utf8mb4");
        
        $this->model = new AuthRule();
        $adminPath = dirname(__DIR__) . DS;
        //控制器名
        $controller = $input->getOption('controller') ?: '';
        if (!$controller) {
            throw new Exception("please input controller name");
        }
        $force = $input->getOption('force');
        //是否为删除模式
        $delete = $input->getOption('delete');
        //是否控制器完全匹配
        $equal = $input->getOption('equal');


        if ($delete) {
            if (in_array('all-controller', $controller)) {
                throw new Exception("could not delete all menu");
            }
            $ids = [];
            $list = $this->model->where(function ($query) use ($controller, $equal) {
                foreach ($controller as $index => $item) {
                    if (stripos($item, '_') !== false) {
                        $item = Loader::parseName($item, 1);
                    }
                    if (stripos($item, '/') !== false) {
                        $controllerArr = explode('/', $item);
                        end($controllerArr);
                        $key = key($controllerArr);
                        $controllerArr[$key] = Loader::parseName($controllerArr[$key]);
                    } else {
                        $controllerArr = [Loader::parseName($item)];
                    }
                    $item = str_replace('_', '\_', implode('/', $controllerArr));
                    if ($equal) {
                        $query->whereOr('name', 'eq', $item);
                    } else {
                        $query->whereOr('name', 'like', strtolower($item) . "%");
                    }
                }
            })->select();
            foreach ($list as $k => $v) {
                $output->warning($v->name);
                $ids[] = $v->id;
            }
            if (!$ids) {
                throw new Exception("There is no menu to delete");
            }
            if (!$force) {
                $output->info("Are you sure you want to delete all those menu?  Type 'yes' to continue: ");
                $line = fgets(defined('STDIN') ? STDIN : fopen('php://stdin', 'r'));
                if (trim($line) != 'yes') {
                    throw new Exception("Operation is aborted!");
                }
            }
            AuthRule::destroy($ids);

            Cache::rm("__menu__");
            $output->info("Delete Successed");
            return;
        }

        if (!in_array('all-controller', $controller)) {
            foreach ($controller as $index => $item) {
                if (stripos($item, '_') !== false) {
                    $item = Loader::parseName($item, 1);
                }
                if (stripos($item, '/') !== false) {
                    $controllerArr = explode('/', $item);
                    end($controllerArr);
                    $key = key($controllerArr);
                    $controllerArr[$key] = Loader::parseName($controllerArr[$key]);
                } else {
                    $controllerArr = [Loader::parseName($item)];
                }
                $this->importRule(implode('/', $controllerArr));
            }
        } else {
            $controllerPath = $adminPath . 'controller' . DS;
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($controllerPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($files as $name => $file) {
                if ($file->isFile() && $file->getExtension() == 'php') {
                    $filePath = $file->getRealPath();
                    $controllerName = str_replace([$controllerPath, '.php', DS], ['', '', '/'], $filePath);
                    $controllerName = strtolower($controllerName);
                    $this->importRule($controllerName);
                }
            }
        }
        Cache::rm("__menu__");
        $output->info("Build Successed!");
    }

    protected function getMenuArr($controller)
    {
        $controller = str_replace('\\', '/', $controller);
        if (stripos($controller, '/') !== false) {
            $controllerArr = explode('/', $controller);
            end($controllerArr);
            $key = key($controllerArr);
            $controllerArr[$key] = ucfirst($controllerArr[$key]);
        } else {
            $key = 0;
            $controllerArr = [ucfirst($controller)];
        }
        $classSuffix = Config::get('controller_suffix') ? ucfirst(Config::get('url_controller_layer')) : '';
        $className = "\\app\\admin\\controller\\" . implode("\\", $controllerArr) . $classSuffix;

        $pathArr = $controllerArr;
        array_unshift($pathArr, '', 'application', 'admin', 'controller');
        $classFile = ROOT_PATH . implode(DS, $pathArr) . $classSuffix . ".php";
        $classContent = file_get_contents($classFile);
        $uniqueName = uniqid("FastAdmin") . $classSuffix;
        $classContent = str_replace("class " . $controllerArr[$key] . $classSuffix . " ", 'class ' . $uniqueName . ' ', $classContent);
        $classContent = preg_replace("/namespace\s(.*);/", 'namespace ' . __NAMESPACE__ . ";", $classContent);

        //临时的类文件
        $tempClassFile = __DIR__ . DS . $uniqueName . ".php";
        file_put_contents($tempClassFile, $classContent);
        $className = "\\app\\admin\\command\\" . $uniqueName;

        //删除临时文件
        register_shutdown_function(function () use ($tempClassFile) {
            if ($tempClassFile) {
                //删除临时文件
                @unlink($tempClassFile);
            }
        });

        //反射机制调用类的注释和方法名
        $reflector = new ReflectionClass($className);

        //只匹配公共的方法
        $methods = $reflector->getMethods(ReflectionMethod::IS_PUBLIC);
        $classComment = $reflector->getDocComment();
        //判断是否有启用软删除
        $softDeleteMethods = ['destroy', 'restore', 'recyclebin'];
        $withSofeDelete = false;
        $modelRegexArr = ["/\\\$this\->model\s*=\s*model\(['|\"](\w+)['|\"]\);/", "/\\\$this\->model\s*=\s*new\s+([a-zA-Z\\\]+);/"];
        $modelRegex = preg_match($modelRegexArr[0], $classContent) ? $modelRegexArr[0] : $modelRegexArr[1];
        preg_match_all($modelRegex, $classContent, $matches);
        if (isset($matches[1]) && isset($matches[1][0]) && $matches[1][0]) {
            \think\Request::instance()->module('admin');
            $model = model($matches[1][0]);
            if (in_array('trashed', get_class_methods($model))) {
                $withSofeDelete = true;
            }
        }
        //忽略的类
        if (stripos($classComment, "@internal") !== false) {
            return;
        }
        preg_match_all('#(@.*?)\n#s', $classComment, $annotations);
        $controllerIcon = 'fa fa-circle-o';
        $controllerRemark = '';
        //判断注释中是否设置了icon值
        if (isset($annotations[1])) {
            foreach ($annotations[1] as $tag) {
                if (stripos($tag, '@icon') !== false) {
                    $controllerIcon = substr($tag, stripos($tag, ' ') + 1);
                }
                if (stripos($tag, '@remark') !== false) {
                    $controllerRemark = substr($tag, stripos($tag, ' ') + 1);
                }
            }
        }
        //过滤掉其它字符
        $controllerTitle = trim(preg_replace(array('/^\/\*\*(.*)[\n\r\t]/u', '/[\s]+\*\//u', '/\*\s@(.*)/u', '/[\s|\*]+/u'), '', $classComment));
        
        // 确保标题是UTF-8编码
        // 如果文件不是UTF-8，先检测并转换
        if (!mb_check_encoding($controllerTitle, 'UTF-8')) {
            $controllerTitle = mb_convert_encoding($controllerTitle, 'UTF-8', 'auto');
        }
        // 如果检测到可能是其他编码（如GBK），强制转换为UTF-8
        if (mb_detect_encoding($controllerTitle, ['UTF-8', 'GBK', 'GB2312'], true) !== 'UTF-8') {
            $detected = mb_detect_encoding($controllerTitle, ['GBK', 'GB2312', 'UTF-8'], true);
            if ($detected && $detected !== 'UTF-8') {
                $controllerTitle = mb_convert_encoding($controllerTitle, 'UTF-8', $detected);
            }
        }

        //导入中文语言包
        \think\Lang::load(dirname(__DIR__) . DS . 'lang/zh-cn.php');

        //先导入菜单的数据
        $name = '';
        $pid = 0;
        foreach ($controllerArr as $k => $v) {
            $key = $k + 1;
            //驼峰转下划线
            $controllerNameArr = array_slice($controllerArr, 0, $key);
            foreach ($controllerNameArr as &$val) {
                $val = strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $val), "_"));
            }
            unset($val);
            $name = implode('/', $controllerNameArr);
            $title = (!isset($controllerArr[$key]) ? $controllerTitle : '');
            $icon = (!isset($controllerArr[$key]) ? $controllerIcon : 'fa fa-list');
            $remark = (!isset($controllerArr[$key]) ? $controllerRemark : '');
            $title = $title ? $title : $v;
            
            // 确保所有字符串都是UTF-8编码
            $title = $this->ensureUtf8($title);
            $remark = $this->ensureUtf8($remark);
            
            $rulemodel = $this->model->get(['name' => $name]);
            if (!$rulemodel) {
                // 使用模型保存，但确保UTF-8编码
                $this->model->data([
                    'pid' => $pid,
                    'name' => $name,
                    'title' => $title,
                    'icon' => $icon,
                    'remark' => $remark,
                    'ismenu' => 1,
                    'status' => 'normal'
                ])->isUpdate(false)->save();
                $pid = $this->model->id;
            } else {
                $pid = $rulemodel->id;
            }
        }
        $ruleArr = [];
        foreach ($methods as $m => $n) {
            //过滤特殊的类
            if (substr($n->name, 0, 2) == '__' || $n->name == '_initialize') {
                continue;
            }
            //未启用软删除时过滤相关方法
            if (!$withSofeDelete && in_array($n->name, $softDeleteMethods)) {
                continue;
            }
            //只匹配符合的方法
            if (!preg_match('/^(\w+)' . Config::get('action_suffix') . '/', $n->name, $matchtwo)) {
                unset($methods[$m]);
                continue;
            }
            $comment = $reflector->getMethod($n->name)->getDocComment();
            //忽略的方法
            if (stripos($comment, "@internal") !== false) {
                continue;
            }
            //过滤掉其它字符
            $comment = preg_replace(array('/^\/\*\*(.*)[\n\r\t]/u', '/[\s]+\*\//u', '/\*\s@(.*)/u', '/[\s|\*]+/u'), '', $comment);

            $title = $comment ? $comment : ucfirst($n->name);
            
            // 确保标题是UTF-8编码
            $title = $this->ensureUtf8($title);

            //获取主键，作为AuthRule更新依据
            $id = $this->getAuthRulePK($name . "/" . strtolower($n->name));

            $ruleArr[] = array('id' => $id, 'pid' => $pid, 'name' => $name . "/" . strtolower($n->name), 'icon' => 'fa fa-circle-o', 'title' => $title, 'ismenu' => 0, 'status' => 'normal');
        }
        
        // 批量插入时也确保UTF-8编码
        foreach ($ruleArr as &$rule) {
            $rule['title'] = $this->ensureUtf8($rule['title']);
        }
        unset($rule);
        
        if (!empty($ruleArr)) {
            // 使用模型的saveAll方法，但确保UTF-8编码
            $this->model->isUpdate(false)->saveAll($ruleArr);
        }
    }

    /**
     * 确保字符串是UTF-8编码
     * 
     * @param string $str
     * @return string
     */
    protected function ensureUtf8($str)
    {
        if (empty($str)) {
            return $str;
        }
        
        // 如果已经是UTF-8，直接返回
        if (mb_check_encoding($str, 'UTF-8')) {
            return $str;
        }
        
        // 检测编码并转换
        $detected = mb_detect_encoding($str, ['UTF-8', 'GBK', 'GB2312', 'ISO-8859-1'], true);
        if ($detected && $detected !== 'UTF-8') {
            $str = mb_convert_encoding($str, 'UTF-8', $detected);
        } else {
            // 如果检测失败，尝试强制转换
            $str = mb_convert_encoding($str, 'UTF-8', 'auto');
        }
        
        return $str;
    }

    //获取主键
    protected function getAuthRulePK($name)
    {
        if (!empty($name)) {
            $id = $this->model
                ->where('name', $name)
                ->value('id');
            return $id ? $id : null;
        }
    }
}
