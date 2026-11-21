<?php

namespace Tests\Unit;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Support\Facades\Facade;
use mysqli;
use PHPUnit\Framework\TestCase;

class MidtransMariaDbConcurrencyTest extends TestCase
{
    public function test_payment_stock_and_review_locks_use_committed_state(): void
    {
        if (getenv('PPL_MIDTRANS_CONCURRENCY') !== '1') {
            self::markTestSkipped('Memerlukan instance MariaDB uji terisolasi pada port 3317.');
        }

        $writer = new mysqli('127.0.0.1', 'root', '', '', 3317);
        $reader = new mysqli('127.0.0.1', 'root', '', '', 3317);
        $identity = $writer->query('SELECT @@datadir AS dir, @@port AS port')->fetch_assoc();
        self::assertStringContainsString('ppl-midtrans-concurrency-', $identity['dir']);
        self::assertSame('3317', (string) $identity['port']);
        $writer->select_db('db_ppl_concurrency_test');
        $reader->select_db('db_ppl_concurrency_test');
        $prefix = 'm'.bin2hex(random_bytes(2)).'_';

        $app = new Container;
        $capsule = new Manager($app);
        $capsule->addConnection([
            'driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 3317,
            'database' => 'db_ppl_concurrency_test', 'username' => 'root', 'password' => '',
            'prefix' => $prefix, 'prefix_indexes' => true,
        ]);
        $db = $capsule->getConnection();
        $app->instance('db', $capsule->getDatabaseManager());
        $previous = Facade::getFacadeApplication();
        Facade::clearResolvedInstance('db');
        Facade::setFacadeApplication($app);
        try {
            foreach (['users', 'pesanan', 'produk'] as $table) {
                $writer->query("CREATE TABLE {$prefix}{$table} (id BIGINT UNSIGNED PRIMARY KEY, state INT DEFAULT 0) ENGINE=InnoDB");
            }
            $writer->query("CREATE TABLE {$prefix}pembayaran (id BIGINT UNSIGNED PRIMARY KEY, pesanan_id BIGINT UNSIGNED, INDEX(pesanan_id,id)) ENGINE=InnoDB");
            $writer->query("CREATE TABLE {$prefix}midtrans_rekonsiliasi (id BIGINT UNSIGNED PRIMARY KEY,
                pembayaran_id BIGINT UNSIGNED, status_penanganan VARCHAR(32), periksa_ulang_at DATETIME NULL) ENGINE=InnoDB");
            $writer->query("INSERT INTO {$prefix}midtrans_rekonsiliasi VALUES (1,1,'selesai',NULL)");
            (require __DIR__.'/../../database/migrations/2026_11_20_000003_add_midtrans_fulfillment_audit.php')->up();
            $writer->query("INSERT INTO {$prefix}pesanan VALUES (1,0)");
            $writer->query("INSERT INTO {$prefix}pembayaran VALUES (1,1)");
            $writer->query("INSERT INTO {$prefix}produk VALUES (1,1)");

            // Webhook dan admin memakai lock induk yang sama.
            $this->lockOrder($writer, $prefix);
            $this->startWaiting($reader, "SELECT id FROM {$prefix}pesanan WHERE id=1 FOR UPDATE");
            $writer->query("UPDATE {$prefix}midtrans_rekonsiliasi SET status_penahanan='ditahan' WHERE id=1");
            $writer->commit();
            $reader->reap_async_query();
            $reader->query("SELECT id FROM {$prefix}pembayaran WHERE pesanan_id=1 ORDER BY id FOR UPDATE");
            $case = $reader->query("SELECT status_penanganan, status_penahanan FROM {$prefix}midtrans_rekonsiliasi WHERE id=1 FOR UPDATE")->fetch_assoc();
            self::assertSame(['status_penanganan' => 'selesai', 'status_penahanan' => 'ditahan'], $case);
            $reader->commit();

            // Checkout kedua melihat stok setelah checkout pertama commit.
            $writer->begin_transaction();
            $writer->query("UPDATE {$prefix}produk SET state=0 WHERE id=1");
            $this->startWaiting($reader, "SELECT state FROM {$prefix}produk WHERE id=1 FOR UPDATE");
            $writer->commit();
            self::assertSame('0', (string) $reader->reap_async_query()->fetch_assoc()['state']);
            $reader->commit();

            // Operator kedua baru membaca reservasi pemeriksaan setelah operator pertama commit.
            $this->lockOrder($writer, $prefix);
            $this->startWaiting($reader, "SELECT id FROM {$prefix}pesanan WHERE id=1 FOR UPDATE");
            $writer->query("UPDATE {$prefix}midtrans_rekonsiliasi SET periksa_ulang_at=DATE_ADD(NOW(), INTERVAL 1 MINUTE) WHERE id=1");
            $writer->commit();
            $reader->reap_async_query();
            $reader->query("SELECT id FROM {$prefix}pembayaran WHERE pesanan_id=1 ORDER BY id FOR UPDATE");
            self::assertNotNull($reader->query("SELECT periksa_ulang_at FROM {$prefix}midtrans_rekonsiliasi WHERE id=1 FOR UPDATE")->fetch_assoc()['periksa_ulang_at']);
            $reader->commit();
        } finally {
            $db->disconnect();
            $reader->close();
            $writer->close();
            Facade::clearResolvedInstance('db');
            Facade::setFacadeApplication($previous);
        }
    }

    private function lockOrder(mysqli $connection, string $prefix): void
    {
        $connection->begin_transaction();
        $connection->query("SELECT id FROM {$prefix}pesanan WHERE id=1 FOR UPDATE");
        $connection->query("SELECT id FROM {$prefix}pembayaran WHERE pesanan_id=1 ORDER BY id FOR UPDATE");
        $connection->query("SELECT id FROM {$prefix}midtrans_rekonsiliasi WHERE pembayaran_id=1 ORDER BY id FOR UPDATE");
    }

    private function startWaiting(mysqli $connection, string $sql): void
    {
        $connection->begin_transaction();
        $connection->query('SET SESSION innodb_lock_wait_timeout=4');
        $connection->query($sql, MYSQLI_ASYNC);
        $read = $error = $reject = [$connection];
        self::assertSame(0, mysqli::poll($read, $error, $reject, 0, 100_000));
    }
}
