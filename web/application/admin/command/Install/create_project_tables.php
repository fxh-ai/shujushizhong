<?php
/**
 * 创建数据时钟项目数据库表
 * 使用方法: php think create_project_tables
 */

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class CreateProjectTables extends Command
{
    protected function configure()
    {
        $this->setName('create_project_tables')
            ->setDescription('Create project database tables');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->info('开始创建数据时钟项目数据库表...');

        try {
            // 1. 批次表
            $this->createBatchesTable($output);
            
            // 2. 币种表
            $this->createCoinsTable($output);
            
            // 3. 行情数据缓存表
            $this->createCoinQuotesTable($output);
            
            // 4. 固件版本表
            $this->createFirmwareVersionsTable($output);
            
            // 5. 系统配置表
            $this->createSystemConfigsTable($output);
            
            // 6. K线数据缓存表
            $this->createCoinOhlcCacheTable($output);
            
            // 7. API限流日志表
            $this->createRateLimitLogsTable($output);
            
            // 插入默认数据
            $this->insertDefaultData($output);
            
            $output->info('✅ 所有表创建完成！');
            
        } catch (\Exception $e) {
            $output->error('❌ 创建表失败: ' . $e->getMessage());
            return;
        }
    }

    private function createBatchesTable(Output $output)
    {
        $sql = "CREATE TABLE IF NOT EXISTS `fa_batches` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL COMMENT '批次名称',
            `api_key` VARCHAR(255) NOT NULL COMMENT '密钥格式不限制，长度不限制',
            `status` TINYINT(1) DEFAULT 1 COMMENT '1:启用 0:禁用',
            `rate_limit` INT(10) DEFAULT 100 COMMENT '限流次数（每分钟）',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_api_key` (`api_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='批次表'";
        
        Db::execute($sql);
        $output->info('✅ 批次表创建成功');
    }

    private function createCoinsTable(Output $output)
    {
        $sql = "CREATE TABLE IF NOT EXISTS `fa_coins` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `coin_id` VARCHAR(50) NOT NULL COMMENT 'CoinGecko的币种ID',
            `symbol` VARCHAR(20) NOT NULL,
            `name` VARCHAR(100) NOT NULL COMMENT '币种名称（支持自定义）',
            `custom_name` VARCHAR(100) DEFAULT NULL COMMENT '自定义名称（如果设置，优先使用）',
            `description` TEXT COMMENT '币种描述（支持自定义）',
            `icon_url` VARCHAR(255) DEFAULT NULL COMMENT 'CoinGecko的原始Logo URL',
            `logo_path` VARCHAR(255) DEFAULT NULL COMMENT '本地存储的Logo路径',
            `custom_logo_path` VARCHAR(255) DEFAULT NULL COMMENT '自定义Logo路径（如果设置，优先使用）',
            `status` TINYINT(1) DEFAULT 1 COMMENT '1:启用 0:禁用',
            `sort_order` INT(10) DEFAULT 0 COMMENT '排序顺序',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_coin_id` (`coin_id`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='币种表'";
        
        Db::execute($sql);
        $output->info('✅ 币种表创建成功');
    }

    private function createCoinQuotesTable(Output $output)
    {
        $sql = "CREATE TABLE IF NOT EXISTS `fa_coin_quotes` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `coin_id` VARCHAR(50) NOT NULL,
            `price` DECIMAL(20, 8) DEFAULT NULL,
            `price_change_24h` DECIMAL(20, 8) DEFAULT NULL,
            `price_change_percentage_24h` DECIMAL(10, 4) DEFAULT NULL,
            `market_cap` DECIMAL(20, 2) DEFAULT NULL,
            `volume_24h` DECIMAL(20, 2) DEFAULT NULL,
            `currency` VARCHAR(10) DEFAULT 'USD',
            `cached_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_coin_quotes_coin_id` (`coin_id`),
            KEY `idx_coin_quotes_cached_at` (`cached_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='行情数据缓存表'";
        
        Db::execute($sql);
        $output->info('✅ 行情数据缓存表创建成功');
    }

    private function createFirmwareVersionsTable(Output $output)
    {
        $sql = "CREATE TABLE IF NOT EXISTS `fa_firmware_versions` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `version` VARCHAR(20) NOT NULL COMMENT '语义化版本号，如：1.0.0',
            `file_path` VARCHAR(255) DEFAULT NULL COMMENT '固件文件路径',
            `file_size` INT(10) DEFAULT NULL COMMENT '文件大小（字节）',
            `download_url` VARCHAR(255) DEFAULT NULL COMMENT '下载URL',
            `release_notes` TEXT COMMENT '发布说明',
            `force_update` TINYINT(1) DEFAULT 0 COMMENT '是否强制更新',
            `status` TINYINT(1) DEFAULT 1 COMMENT '1:启用 0:禁用',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_version` (`version`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='固件版本表'";
        
        Db::execute($sql);
        $output->info('✅ 固件版本表创建成功');
    }

    private function createSystemConfigsTable(Output $output)
    {
        $sql = "CREATE TABLE IF NOT EXISTS `fa_system_configs` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `config_key` VARCHAR(50) NOT NULL,
            `config_value` TEXT,
            `config_type` VARCHAR(20) DEFAULT 'string' COMMENT 'string, int, json',
            `description` VARCHAR(255) DEFAULT NULL,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_config_key` (`config_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表'";
        
        Db::execute($sql);
        $output->info('✅ 系统配置表创建成功');
    }

    private function createCoinOhlcCacheTable(Output $output)
    {
        $sql = "CREATE TABLE IF NOT EXISTS `fa_coin_ohlc_cache` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `coin_id` VARCHAR(50) NOT NULL,
            `vs_currency` VARCHAR(10) DEFAULT 'USD',
            `days` VARCHAR(10) NOT NULL COMMENT '5m, 1h, 1d',
            `ohlc_data` TEXT NOT NULL COMMENT 'JSON格式存储K线数据',
            `cached_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_coin_ohlc_cache_lookup` (`coin_id`, `vs_currency`, `days`),
            KEY `idx_coin_ohlc_cache_cached_at` (`cached_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='K线数据缓存表'";
        
        Db::execute($sql);
        $output->info('✅ K线数据缓存表创建成功');
    }

    private function createRateLimitLogsTable(Output $output)
    {
        $sql = "CREATE TABLE IF NOT EXISTS `fa_rate_limit_logs` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `batch_id` INT(10) UNSIGNED NOT NULL,
            `api_path` VARCHAR(255) NOT NULL,
            `request_count` INT(10) DEFAULT 1,
            `minute` INT(10) NOT NULL COMMENT '分钟时间戳',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_batch_minute` (`batch_id`, `minute`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API限流日志表'";
        
        Db::execute($sql);
        $output->info('✅ API限流日志表创建成功');
    }

    private function insertDefaultData(Output $output)
    {
        // 插入默认系统配置
        $configs = [
            ['refresh_interval', '300', 'int', '数据刷新间隔（秒），默认300秒（5分钟）'],
            ['default_currency', 'USD', 'string', '默认计价货币'],
            ['display_coins', '["bitcoin", "ethereum"]', 'json', '默认显示的币种列表'],
            ['timezone', 'Asia/Shanghai', 'string', '时区设置'],
            ['display_format', 'standard', 'string', '显示格式配置'],
            ['coingecko_api_key', '', 'string', 'CoinGecko API密钥（可选）'],
        ];

        foreach ($configs as $config) {
            Db::name('system_configs')->insert([
                'config_key' => $config[0],
                'config_value' => $config[1],
                'config_type' => $config[2],
                'description' => $config[3],
            ], true);
        }
        $output->info('✅ 默认系统配置插入成功');

        // 插入测试批次
        $batchExists = Db::name('batches')->where('api_key', 'test_api_key_123456')->find();
        if (!$batchExists) {
            Db::name('batches')->insert([
                'name' => '测试批次',
                'api_key' => 'test_api_key_123456',
                'status' => 1,
                'rate_limit' => 100,
            ]);
            $output->info('✅ 测试批次插入成功');
        } else {
            $output->info('ℹ️  测试批次已存在');
        }
    }
}

