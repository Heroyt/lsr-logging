<?php

namespace TestCases;

use DateTimeImmutable;
use Lsr\Logging\Exceptions\ArchiveCreationException;
use Lsr\Logging\FsHelper;
use Lsr\Logging\LogArchiver;
use PHPUnit\Framework\TestCase;

class LogArchiverTest extends TestCase
{
    private const string LOG_NAME = 'test';

    /** @var string[] */
    private array $dates = [];

    /** @var array<string,string[]> */
    private array $weeks = [];

    public function setUp(): void {
        parent::setUp();

        // Prepare logs to archive
        for ($i = 0; $i < 15; $i++) {
            $time = strtotime('-' . $i . ' days');
            $date = date('Y-m-d', $time);
            $week = date('Y-m-W', $time);
            $this->weeks[$week] ??= [];
            $this->weeks[$week][] = $date;
            $this->dates[] = $date;
            touch(LOG_DIR . $this::LOG_NAME . '-' . $date . '.log');
        }
    }

    public function tearDown(): void {
        parent::tearDown();

        /** @var string $file */
        foreach (glob(LOG_DIR . '*') as $file) {
            @unlink($file);
        }

        if (file_exists(LOG_DIR . 'archive')) {
            foreach (glob(LOG_DIR . 'archive/*') as $file) {
                unlink($file);
            }
            rmdir(LOG_DIR . 'archive');
        }

        $this->dates = [];
        $this->weeks = [];
    }

    /**
     * Test using the default values
     *
     * @return void
     */
    public function testArchiveOld(): void {
        $limit = '-2 days';
        $archiver = new LogArchiver(FsHelper::getInstance(), $limit);
        $maxArchiveDate = new DateTimeImmutable($limit);

        // Preemptively assert that all log files are present
        self::assertNotEmpty($this->dates, 'Initialisation failed');
        foreach ($this->dates as $date) {
            self::assertFileExists(LOG_DIR . self::LOG_NAME . '-' . $date . '.log', 'Initialisation failed - log files are missing');
        }

        $archiver->archiveOld(LOG_DIR, $this::LOG_NAME);

        // By default, only 2 files should remain and the rest should be archived
        self::assertFileExists(LOG_DIR . self::LOG_NAME . '-' . $this->dates[0] . '.log');
        self::assertFileExists(LOG_DIR . self::LOG_NAME . '-' . $this->dates[1] . '.log');

        // All other files should not exist
        for ($i = 2, $count = count($this->dates); $i < $count; $i++) {
            self::assertFileDoesNotExist(LOG_DIR . self::LOG_NAME . '-' . $this->dates[$i] . '.log');
        }

        // Check if all zip archives are created
        $i = 0;
        foreach ($this->weeks as $week => $days) {
            if ($i === 0 && count($days) < 3) {
                $i++;
                continue;
            }
            $zipFile = LOG_DIR . self::LOG_NAME . '-' . $week . '.zip';
            self::assertFileExists($zipFile);

            // Check zip content
            $zip = new \ZipArchive();
            $zip->open($zipFile);
            foreach ($days as $key => $day) {
                if (($i === 0 && $key < 3) || (new DateTimeImmutable($day)) > $maxArchiveDate) {
                    continue;
                }
                $success = $zip->getFromName(self::LOG_NAME . '-' . $day . '.log');
                self::assertNotFalse($success);
            }
            $zip->close();
            $i++;
        }
    }

    /**
     * Test using the default values
     *
     * @return void
     */
    public function testArchiveOldWithNoFiles(): void {
        $archiver = new LogArchiver(FsHelper::getInstance());

        $filesBefore = glob(LOG_DIR . '*');
        self::assertEmpty(glob(LOG_DIR . 'file_that_does_not_exist*'));

        // Nothing should happen
        $archiver->archiveOld(LOG_DIR, 'file_that_does_not_exist');

        $filesAfter = glob(LOG_DIR . '*');
        self::assertEquals($filesBefore, $filesAfter);
    }

    /**
     * Test using the default values
     *
     * @return void
     */
    public function testArchiveOldWithSubDir(): void {
        $limit = '-2 days';
        $archiver = new LogArchiver(FsHelper::getInstance(), $limit);
        $maxArchiveDate = new DateTimeImmutable($limit);

        // Preemptively assert that all log files are present
        self::assertNotEmpty($this->dates, 'Initialisation failed');
        foreach ($this->dates as $date) {
            self::assertFileExists(LOG_DIR . self::LOG_NAME . '-' . $date . '.log', 'Initialisation failed - log files are missing');
        }

        self::assertDirectoryDoesNotExist(LOG_DIR . 'archive');

        $archiver->archiveOld(LOG_DIR, $this::LOG_NAME, 'archive');

        self::assertDirectoryExists(LOG_DIR . 'archive');

        // By default, only 2 files should remain and the rest should be archived
        self::assertFileExists(LOG_DIR . self::LOG_NAME . '-' . $this->dates[0] . '.log');
        self::assertFileExists(LOG_DIR . self::LOG_NAME . '-' . $this->dates[1] . '.log');

        // All other files should not exist
        for ($i = 2, $count = count($this->dates); $i < $count; $i++) {
            self::assertFileDoesNotExist(LOG_DIR . self::LOG_NAME . '-' . $this->dates[$i] . '.log');
        }

        // Check if all zip archives are created
        $i = 0;
        foreach ($this->weeks as $week => $days) {
            if ($i === 0 && count($days) < 3) {
                $i++;
                continue;
            }
            $zipFile = LOG_DIR . 'archive/' . self::LOG_NAME . '-' . $week . '.zip';
            self::assertFileExists($zipFile);

            // Check zip content
            $zip = new \ZipArchive();
            $zip->open($zipFile);
            foreach ($days as $key => $day) {
                if (($i === 0 && $key < 3) || (new DateTimeImmutable($day)) > $maxArchiveDate) {
                    continue;
                }
                $success = $zip->getFromName(self::LOG_NAME . '-' . $day . '.log');
                self::assertNotFalse($success);
            }
            $zip->close();
            $i++;
        }
    }

    /**
     * Test using the default values
     *
     * @return void
     */
    public function testArchiveOldWithAbsoluteSubDir(): void {
        $limit = '-2 days';
        $archiver = new LogArchiver(FsHelper::getInstance(), $limit);
        $maxArchiveDate = new DateTimeImmutable($limit);

        // Preemptively assert that all log files are present
        self::assertNotEmpty($this->dates, 'Initialisation failed');
        foreach ($this->dates as $date) {
            self::assertFileExists(LOG_DIR . self::LOG_NAME . '-' . $date . '.log', 'Initialisation failed - log files are missing');
        }

        self::assertDirectoryDoesNotExist(LOG_DIR . 'archive');

        $archiver->archiveOld(LOG_DIR, $this::LOG_NAME, LOG_DIR . 'archive');

        self::assertDirectoryExists(LOG_DIR . 'archive');

        // By default, only 2 files should remain and the rest should be archived
        self::assertFileExists(LOG_DIR . self::LOG_NAME . '-' . $this->dates[0] . '.log');
        self::assertFileExists(LOG_DIR . self::LOG_NAME . '-' . $this->dates[1] . '.log');

        // All other files should not exist
        for ($i = 2, $count = count($this->dates); $i < $count; $i++) {
            self::assertFileDoesNotExist(LOG_DIR . self::LOG_NAME . '-' . $this->dates[$i] . '.log');
        }

        // Check if all zip archives are created
        $i = 0;
        foreach ($this->weeks as $week => $days) {
            if ($i === 0 && count($days) < 3) {
                $i++;
                continue;
            }
            $zipFile = LOG_DIR . 'archive/' . self::LOG_NAME . '-' . $week . '.zip';
            self::assertFileExists($zipFile);

            // Check zip content
            $zip = new \ZipArchive();
            $zip->open($zipFile);
            foreach ($days as $key => $day) {
                if (($i === 0 && $key < 3) || (new DateTimeImmutable($day)) > $maxArchiveDate) {
                    continue;
                }
                $success = $zip->getFromName(self::LOG_NAME . '-' . $day . '.log');
                self::assertNotFalse($success);
            }
            $zip->close();
            $i++;
        }
    }

    /**
     * @testWith [1]
     *           [4]
     *           [7]
     *
     * @return void
     */
    public function testArchiveOldWithDifferentLimits(int $limit): void {
        $archiver = new LogArchiver(FsHelper::getInstance(), '-' . $limit . ' days');

        // Preemptively assert that all log files are present
        self::assertNotEmpty($this->dates, 'Initialisation failed');
        foreach ($this->dates as $date) {
            self::assertFileExists(LOG_DIR . self::LOG_NAME . '-' . $date . '.log', 'Initialisation failed - log files are missing');
        }

        $archiver->archiveOld(LOG_DIR, $this::LOG_NAME);

        // Only $limit files should remain and the rest should be archived
        for ($i = 0; $i < $limit; $i++) {
            self::assertFileExists(LOG_DIR . self::LOG_NAME . '-' . $this->dates[$i] . '.log');
        }

        // All other files should not exist
        for ($i = $limit, $count = count($this->dates); $i < $count; $i++) {
            self::assertFileDoesNotExist(LOG_DIR . self::LOG_NAME . '-' . $this->dates[$i] . '.log');
        }

        $weeks = $this->weeks;
        foreach ($weeks as $week => $days) {
            if ($limit < 1) {
                continue;
            }
            $count = count($days);

            if ($limit > $count) {
                $weeks[$week] = [];
                $limit -= $count;
                continue;
            }

            for ($j = 0; $j < $limit; $j++) {
                array_shift($days);
            }
            $weeks[$week] = $days;
            $limit = 0;
        }

        // Check if all zip archives are created
        foreach ($weeks as $week => $days) {
            if (count($days) === 0) {
                continue;
            }
            $zipFile = LOG_DIR . self::LOG_NAME . '-' . $week . '.zip';
            self::assertFileExists($zipFile);

            // Check zip content
            $zip = new \ZipArchive();
            $zip->open($zipFile);
            foreach ($days as $day) {
                $success = $zip->getFromName(self::LOG_NAME . '-' . $day . '.log');
                self::assertNotFalse($success);
            }
            $zip->close();
        }
    }

    public function testWithProtectedDirectory(): void {
        $archiver = new LogArchiver(FsHelper::getInstance());

        $this->expectException(ArchiveCreationException::class);
        $archiver->archiveOld(LOG_DIR, $this::LOG_NAME, 'protected');
    }
}
