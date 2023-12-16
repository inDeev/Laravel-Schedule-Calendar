<?php

declare(strict_types=1);

namespace Indeev\LaravelScheduleCalendar\Console\Commands;

use Closure;
use DateTime;
use Exception;
use Carbon\Carbon;
use Cron\CronExpression;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Console\Application;
use Symfony\Component\Console\Terminal;
use Illuminate\Console\Scheduling\Schedule;

class ScheduleCalendarCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'schedule:calendar
                            {--date=today : Range of calendar to display (today, yyyy-mm-dd)}
                            {--range=day : Range of calendar to display (day, week)}
                            {--hoursPerLine=12 : Number of hours per line (1, 2, 3, 4, 6, 8, 12, 24)}
                            {--display=count : Display type (dot, count, list)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display scheduled tasks in calendar view';

    /**
     * The terminal width resolver callback.
     *
     * @var Closure|null
     */
    protected static $terminalWidthResolver;

    /**
     * @var int $hoursPerLine The number of hours per calendar line.
     */
    protected $hoursPerLine;

    /**
     *
     * @var array $allowedHoursPerLine The allowed number of hours per calendar line.
     */
    protected $allowedHoursPerLine = [1, 2, 3, 4, 6, 8, 12, 24];

    /**
     * @var int $hourWidth The number of characters per one hour field.
     */
    protected $hourWidth;

    /**
     * @var float $minutesPerField Real amount of minutes inside one displayed minute character.
     */
    protected $minutesPerField;

    /**
     * @var string $display Display type of scheduled tasks (dot, count, list).
     */
    protected $display;

    /**
     * @var array $commands List of symbols with associated command.
     */
    protected $commands;

    /**
     * @var array $scheduledTasks List of scheduled tasks per datetime.
     */
    protected $scheduledTasks = [];

    /**
     * @var array $printInfo Information used to print to terminal.
     */
    protected $printInfo = [
        'max_commands' => 0,
        'max_lines' => 1,
        'used_symbols' => [],
    ];

    /**
     * Execute the console command.
     *
     * @param  Schedule  $schedule
     *
     * @throws Exception
     */
    public function handle(Schedule $schedule): void
    {
        $date = $this->option('date');
        if ($date === 'today') {
            $date = today();
        } elseif (preg_match('#^\d{4}-\d{2}-\d{2}$#', $date)) {
            $date = Carbon::parse($date);
        } else {
            $this->error('Date must be "today" or date in format "yyyy-mm-dd".');
            return;
        }

        $range = $this->option('range');
        if (!in_array($range, ['day', 'week'], true)) {
            $this->error('Range must be one of "day" or "week".');
            return;
        }

        $hoursPerLine = $this->option('hoursPerLine');
        if (!in_array($hoursPerLine, $this->allowedHoursPerLine, false)) {
            $this->error('Hours per line must be one of '.implode(', ', $this->allowedHoursPerLine).'.');
            return;
        }
        $this->hoursPerLine = (int) $hoursPerLine;

        $display = $this->option('display');
        if (!in_array($display, ['dot', 'count', 'list'], true)) {
            $this->error('Display must be one of "dot", "count", "list".');
            return;
        }
        $this->display = $display;

        $terminalWidth = self::getTerminalWidth();

        $this->hourWidth = (int) (($terminalWidth - 1) / $this->hoursPerLine);
        $this->minutesPerField = 60 / ($this->hourWidth - 1);
        $selectedIndex = array_search($this->hoursPerLine, $this->allowedHoursPerLine, true);
        while ($this->hourWidth < 7 && $selectedIndex > 0) {
            $this->hoursPerLine = $this->allowedHoursPerLine[$selectedIndex - 1];
            $this->hourWidth = (int) (($terminalWidth - 1) / $this->hoursPerLine);
            $this->minutesPerField = 60 / ($this->hourWidth - 1);
            $selectedIndex = array_search($this->hoursPerLine, $this->allowedHoursPerLine, true);
        }
        if ($this->hoursPerLine !== (int) $this->option('hoursPerLine')) {
            $this->error('Terminal width is too small. Hours per line adjusted to '.$this->hoursPerLine.'.');
        }

        if ($range === 'week') {
            $start = $date->copy()->startOfWeek();
            $end = $date->copy()->endOfWeek();
        } else {
            $start = $date->copy()->startOfDay();
            $end = $date->copy()->endOfDay();
        }

        $period = new CarbonPeriod($start, '1 day', $end);

        $this->prepareDatetimeArray($period);

        $this->mapTasks($schedule, $start, $end);

        $this->printCalendar($period);
    }

    /**
     * Prepare array of datetime for calendar.
     */
    private function prepareDatetimeArray(CarbonPeriod $period): void
    {
        /** @var Carbon $day */
        foreach ($period as $day) {
            for ($hour = 0; $hour < 24; $hour++) {
                for ($minutes = 0; $minutes < $this->hourWidth - 1; $minutes++) {
                    $fieldStart = $day->copy()
                        ->addHours($hour)
                        ->addMinutes($minutes * (int) $this->minutesPerField)
                        ->addSeconds($minutes * (int) (60 * ($this->minutesPerField - (int) $this->minutesPerField)));
                    $this->scheduledTasks[$day->toDateString()][$hour][$fieldStart->toDateTimeString()] = [];
                }
            }
        }
    }

    /**
     * Map scheduled tasks to datetime array.
     * @throws Exception
     */
    private function mapTasks(Schedule $schedule, Carbon $start, Carbon $end): void
    {
        $events = collect($schedule->events());

        foreach ($events as $i => $event) {
            $commandSymbol = $this->generateSymbol($i);
            $command = str_replace([Application::phpBinary(), Application::artisanBinary()], [
                'php',
                preg_replace("#['\"]#", '', Application::artisanBinary()),
            ], $event->command);
            $this->commands[$commandSymbol] = $command;

            $cronExpression = new CronExpression($event->expression);

            $nextRunDate = $cronExpression->getNextRunDate($start, 0, true);
            while ($nextRunDate <= $end) {
                $dateString = $nextRunDate->format('Y-m-d');
                $hourString = $nextRunDate->format('G');
                $prevKey = key($this->scheduledTasks[$dateString][$hourString]);
                end($this->scheduledTasks[$dateString][$hourString]);
                $lastKey = key($this->scheduledTasks[$dateString][$hourString]);
                foreach ($this->scheduledTasks[$dateString][$hourString] as $key => $value) {
                    if (new DateTime($key) > $nextRunDate) {
                        $this->attachCommandToDatetime($dateString, $hourString, $prevKey, $commandSymbol);
                        break;
                    }
                    $prevKey = $key;
                }
                if ($prevKey === $lastKey) {
                    $this->attachCommandToDatetime($dateString, $hourString, $prevKey, $commandSymbol);
                }
                $nextRunDate = Carbon::parse($cronExpression->getNextRunDate($nextRunDate, 1, true));
            }
        }
        $this->printInfo['used_symbols'] = array_unique($this->printInfo['used_symbols']);
    }

    /**
     * Attach command symbol to datetime.
     */
    private function attachCommandToDatetime(string $dateString, string $hourString, string $key, string $commandSymbol): void
    {
        $this->scheduledTasks[$dateString][$hourString][$key]['symbols'][] = $commandSymbol;
        $symbolsCount = count($this->scheduledTasks[$dateString][$hourString][$key]['symbols']);
        $this->printInfo['max_commands'] = max($this->printInfo['max_commands'], $symbolsCount);
        $this->printInfo['used_symbols'][] = $commandSymbol;
        if ($this->display !== 'dot') {
            $this->printInfo['max_lines'] = $this->display === 'count'
                ? max($this->printInfo['max_lines'], strlen((string) $symbolsCount))
                : max($this->printInfo['max_lines'], $symbolsCount);
        }
    }

    /**
     * Generate symbol for command by index.
     */
    private function generateSymbol(int $index): string
    {
        $numLowercase = 26;
        $numUppercase = 26;
        $numDigits = 10;

        if ($index < $numLowercase) {
            return chr(ord('a') + $index);
        }

        if ($index < $numLowercase + $numUppercase) {
            return chr(ord('A') + ($index - $numLowercase));
        }

        if ($index < $numLowercase + $numUppercase + $numDigits) {
            return (string) ($index - $numLowercase - $numUppercase);
        }

        $start = 0x2460; // Unicode point for ①

        return html_entity_decode('&#'.($start + $index - 62).';', ENT_COMPAT, 'UTF-8');
    }

    /**
     * Print calendar.
     */
    private function printCalendar(CarbonPeriod $days): void
    {
        $this->line(str_pad('Legend', ($this->hourWidth * $this->hoursPerLine + 1), ' ', STR_PAD_BOTH), 'bg=blue;fg=bright-white');
        if ($this->printInfo['max_commands'] === 0) {
            $this->line('<fg=red>Your Kernel.php looks empty, let\'s add some scheduled tasks!</>');
        } else {
            $colorStep = $this->printInfo['max_commands'] / 3;
            $this->line('<options=bold;fg=bright-green>●</> - <= '.floor($colorStep).' tasks');
            $this->line('<options=bold;fg=bright-yellow>●</> - <= '.floor($colorStep * 2).' tasks');
            $this->line('<options=bold;fg=bright-red>●</> - <= '.floor($colorStep * 3).' tasks');
            if ($this->display === 'list') {
                foreach ($this->commands as $symbol => $command) {
                    if (in_array($symbol, $this->printInfo['used_symbols'], true)) {
                        $this->line('<fg=red>'.$symbol.'</> - '.$command);
                    }
                }
                $this->line('');
            }
        }

        /** @var Carbon $day */
        foreach ($days as $day) {
            $dayString = $day->toDateString();
            $this->line(str_pad($day->format('l Y-m-d'), ($this->hourWidth * $this->hoursPerLine + 1), ' ', STR_PAD_BOTH), 'bg=blue;fg=bright-white');
            $hour = today();
            for ($lines = 0, $totalLines = 24 / $this->hoursPerLine; $lines < $totalLines; $lines++) {
                $this->printHourLine($hour);
                $this->output->newLine();
                $linesArray = [];
                for ($hours = $lines * $this->hoursPerLine, $maxHour = $lines * $this->hoursPerLine + $this->hoursPerLine; $hours < $maxHour; $hours++) {
                    $linesArray[0][] = ['value' => '|'];
                    for ($minutes = 0; $minutes < $this->hourWidth - 1; $minutes++) {
                        $fieldStart = $day->copy()
                            ->addHours($hours)
                            ->addMinutes($minutes * (int) $this->minutesPerField)
                            ->addSeconds($minutes * (int) (60 * ($this->minutesPerField - (int) $this->minutesPerField)))
                            ->toDateTimeString();
                        $scheduledTask = $this->scheduledTasks[$dayString][$hours][$fieldStart] ?? [];
                        $style = $this->getColorBasedOnMaxTasks($scheduledTask);

                        if ($this->display === 'count') {
                            $minutesFieldCharacter = !empty($scheduledTask['symbols']) ? ['value' => count($scheduledTask['symbols']), 'style' => $style] : ['value' => '⎯'];
                        } elseif ($this->display === 'list') {
                            $minutesFieldCharacter = !empty($scheduledTask['symbols']) ? ['value' => implode('', $scheduledTask['symbols']), 'style' => $style] : ['value' => '⎯'];
                        } else {
                            $minutesFieldCharacter = !empty($scheduledTask['symbols']) ? ['value' => '●', 'style' => $style] : ['value' => '⎯'];
                        }

                        $linesArray[0][] = $minutesFieldCharacter;
                    }
                }
                $linesArray[0][] = ['value' => '|'];

                foreach ($linesArray[0] as $key => $record) {
                    for ($i = 0; $i < $this->printInfo['max_lines']; $i++) {
                        $styleStart = $record['style'] ?? '';
                        $styleEnd = $record['style'] ?? null ? '</>' : '';
                        $character = mb_strlen((string) $record['value']) > $i ? mb_substr((string) $record['value'], $i, 1) : ' ';
                        $linesArray[$i][$key] = $styleStart.$character.$styleEnd;
                    }
                }
                foreach ($linesArray as $lineValue) {
                    $this->output->writeln(implode('', (array) $lineValue));
                }
            }
        }
    }

    /**
     * Print hour line.
     */
    private function printHourLine(Carbon $startHour): void
    {
        for ($hours = 0; $hours < $this->hoursPerLine; $hours++) {
            $this->output->write($startHour->format('H:i'));
            $this->output->write(str_repeat(' ', $this->hourWidth - ($hours === 0 ? 7 : 5)));
            $startHour = $startHour->addHour();
        }
    }

    /**
     * Get color based on max tasks.
     */
    private function getColorBasedOnMaxTasks(array $scheduledTask): string
    {
        $colorStep = $this->printInfo['max_commands'] / 3;
        $scheduledTaskSymbolsCount = count($scheduledTask['symbols'] ?? []);

        if ($scheduledTaskSymbolsCount <= $colorStep) {
            return '<options=bold;fg=bright-green>';
        }

        if ($scheduledTaskSymbolsCount <= $colorStep * 2) {
            return '<options=bold;fg=bright-yellow>';
        }

        if ($scheduledTaskSymbolsCount <= $colorStep * 3) {
            return '<options=bold;fg=bright-red>';
        }

        return 'white';
    }

    /**
     * Get the terminal width.
     */
    public static function getTerminalWidth(): int
    {
        return is_null(static::$terminalWidthResolver)
            ? (new Terminal)->getWidth()
            : call_user_func(static::$terminalWidthResolver);
    }
}
