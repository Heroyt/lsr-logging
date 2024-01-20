<?php

namespace TestCases;

use Dibi\Connection;
use Dibi\DriverException;
use Dibi\Event;
use Exception;
use Generator;
use Lsr\Logging\Logger;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

class LoggerTest extends TestCase
{

	public static function exceptionProvider() : ?Generator {
		$message = 'Exception occured';
		yield [new Exception($message, 123), 'Thrown Exception (123): '.$message];
		yield [new RuntimeException($message, 0), 'Thrown Exception (0): '.$message];
	}

	public static function getDbEvents() : Generator {
		$connection = new Connection(['lazy' => true]);
		$event = new Event($connection, Event::SELECT);
		$event->sql = 'SELECT * FROM table';
		$event->result = new DriverException('Error occured', 123, $event->sql);
		yield [$event, '(123) Error occured', $event->sql];
		$event = new Event($connection, Event::INSERT);
		$event->sql = 'INSERT INTO table (col1, col2, col3) VALUES (1, 2, 3)';
		$event->result = new DriverException('Error occured while inserting', 404, $event->sql);
		yield [$event, '(404) Error occured while inserting', $event->sql];
	}

	public static function logContextProvider() : Generator {
		yield ['debug', 'Test message', ['context' => 123]];
		yield ['WARNING', 'Test message 2', ['context' => 123, 'info' => ['hello', 'world']]];
	}

	public function tearDown() : void {
		parent::tearDown();

		// Remove all old log files
		foreach (glob(LOG_DIR.'**') as $file) {
			unlink($file);
		}

		if (file_exists(LOG_DIR.'dir')) {
			foreach (glob(LOG_DIR.'dir/*') as $file) {
				unlink($file);
			}
			if (!(rmdir(LOG_DIR.'dir'))) {
				throw new RuntimeException('Cannot remove directory');
			}
		}
	}

	/**
	 * @dataProvider exceptionProvider
	 *
	 * @param Throwable $exception
	 * @param string    $expectedMessage
	 * @return void
	 */
	public function testException(Throwable $exception, string $expectedMessage) : void {
		$expectedFileName = LOG_DIR.'exception-'.date('Y-m-d').'.log';
		$logger = new Logger(LOG_DIR, 'exception');

		$logger->exception($exception);

		self::assertFileExists($expectedFileName);


		/** @var string[] $lines */
		$lines = array_filter(explode(PHP_EOL, file_get_contents($expectedFileName)));
		self::assertNotEmpty($lines);

		// Test for error line
		self::assertMatchesRegularExpression(
			'/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}] ERROR: '.
			str_replace(
				['(', '[', ')', ']', '*', '+'],
				['\(', '\[', '\)', '\]', '\*', '\+'],
				$expectedMessage
			).'/', array_shift($lines));

		// Test for trace lines
		$traceMatch = '#\d+ .+';
		self::assertMatchesRegularExpression(
			'/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}] DEBUG: '.$traceMatch.'/',
			array_shift($lines)
		);
		foreach ($lines as $line) {
			self::assertMatchesRegularExpression('/'.$traceMatch.'/', $line);
		}
	}

	/**
	 * @dataProvider getDbEvents
	 *
	 * @param Event  $event
	 * @param string $message
	 * @param string $sql
	 * @return void
	 */
	public function testLogDb(Event $event, string $message, string $sql) : void {
		$expectedFileName = LOG_DIR.'db-'.date('Y-m-d').'.log';
		$logger = new Logger(LOG_DIR, 'db');

		$logger->logDb($event);

		self::assertFileExists($expectedFileName);

		/** @var string[] $lines */
		$lines = array_filter(explode(PHP_EOL, file_get_contents($expectedFileName)));
		self::assertNotEmpty($lines);

		self::assertMatchesRegularExpression(
			'/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}] ERROR: '.
			str_replace(
				['(', '[', ')', ']', '*', '+'],
				['\(', '\[', '\)', '\]', '\*', '\+'],
				$message
			).'/',
			array_shift($lines)
		);

		if (!empty($sql)) {
			self::assertMatchesRegularExpression(
				'/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}] DEBUG: SQL: '.
				str_replace(
					['(', '[', ')', ']', '*', '+'],
					['\(', '\[', '\)', '\]', '\*', '\+'],
					$sql
				).'/',
				array_shift($lines));
		}
	}

	/**
	 * @testWith  ["debug", "Test message"]
	 *            ["warning", "Test message 2"]
	 *            ["SUCCESS", "asdjhabsdahjsdb"]
	 *
	 * @param string $level
	 * @param string $message
	 * @return void
	 */
	public function testLog(string $level, string $message) : void {
		$expectedFileName = LOG_DIR.'test-'.date('Y-m-d').'.log';
		$logger = new Logger(LOG_DIR, 'test');

		// Test normal log
		$logger->log($level, $message);

		self::assertFileExists($expectedFileName);
		self::assertMatchesRegularExpression(
			'/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}] '.strtoupper($level).': '.
			str_replace(
				['(', '[', ')', ']', '*', '+'],
				['\(', '\[', '\)', '\]', '\*', '\+'],
				$message
			).'/',
			file_get_contents($expectedFileName)
		);
	}

	/**
	 * @testWith  ["debug", "Test message"]
	 *            ["warning", "Test message 2"]
	 *            ["SUCCESS", "asdjhabsdahjsdb"]
	 *
	 * @param string $level
	 * @param string $message
	 * @return void
	 */
	public function testLogSubDir(string $level, string $message) : void {
		$expectedFileName = LOG_DIR.'dir/test-'.date('Y-m-d').'.log';
		$logger = new Logger(LOG_DIR.'dir', 'test');

		self::assertDirectoryDoesNotExist(LOG_DIR.'dir');

		// Test normal log
		$logger->log($level, $message);

		self::assertDirectoryExists(LOG_DIR.'dir');
		self::assertFileExists($expectedFileName);
		self::assertMatchesRegularExpression(
			'/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}] '.strtoupper($level).': '.$message.'/',
			file_get_contents($expectedFileName)
		);
	}

	/**
	 * @dataProvider logContextProvider
	 *
	 * @param string $level
	 * @param string $message
	 * @return void
	 */
	public function testLogContext(string $level, string $message, array $context) : void {
		$expectedFileName = LOG_DIR.'context-'.date('Y-m-d').'.log';
		$logger = new Logger(LOG_DIR, 'context');

		// Test normal log
		$logger->log($level, $message, $context);

		self::assertFileExists($expectedFileName);
		$contextFormatted = str_replace(
			['(', '[', ')', ']', '*', '+'],
			['\(', '\[', '\)', '\]', '\*', '\+'],
			json_encode($context, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
		);
		self::assertMatchesRegularExpression(
			'/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}] '.strtoupper($level).': '.$message.' '.$contextFormatted.'/',
			file_get_contents($expectedFileName)
		);
	}

}
