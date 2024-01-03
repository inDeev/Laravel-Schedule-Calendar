# Laravel Schedule Calendar

[![Latest Stable Version](http://poser.pugx.org/indeev/laravel-schedule-calendar/v)](https://packagist.org/packages/indeev/laravel-schedule-calendar)
[![Total Downloads](http://poser.pugx.org/indeev/laravel-schedule-calendar/downloads)](https://packagist.org/packages/indeev/laravel-schedule-calendar)
[![Latest Unstable Version](http://poser.pugx.org/indeev/laravel-schedule-calendar/v/unstable)](https://packagist.org/packages/indeev/laravel-rapid-db-anonymizer)
[![License](http://poser.pugx.org/indeev/laravel-schedule-calendar/license)](https://packagist.org/packages/indeev/laravel-schedule-calendar)

![Laravel Remote DB Sync](https://github.com/inDeev/Laravel-Schedule-Calendar/blob/main/img/LaravelScheduleCalendar.png)

## Overview

The Schedule Calendar command has been introduced to provide developers with a clear and insightful view of scheduled tasks within the Laravel application. This new functionality allows for a visual representation of task distribution throughout the day and week, offering a valuable perspective on load distribution.

## Requirements

- PHP 7.3 or higher
- Laravel 8+

## Key Features

- **Day and Week View:** Easily switch between day and week views to analyze scheduled tasks over different time frames.

- **Load Distribution:** Gain insights into the distribution of scheduled tasks throughout the day, helping identify peak load periods and optimize task scheduling.

- **Enhanced Debugging:** Use the calendar view as a debugging tool to identify potential conflicts or overlaps in scheduled tasks.

## Installation

The Schedule Calendar command is available as a package on [Packagist](https://packagist.org/packages/indeev/laravel-schedule-calendar) and can be installed using [Composer](https://getcomposer.org/).

```bash
composer require indeev/laravel-schedule-calendar
```

## How to Use

To leverage the power of Schedule Calendar, simply run the command in your Laravel application:

```bash
php artisan schedule:calendar
```

This will generate a visual representation of your scheduled tasks, providing a comprehensive overview of your application's task schedule.

![Single day with counts](https://github.com/inDeev/Laravel-Schedule-Calendar/blob/main/img/single_day_count.png)

## Display Option: `--display=dot`

The `--display=dot` option provides a visual representation of your scheduled tasks using dots, offering a clear and concise overview. Each dot represents all scheduled tasks in time piece, making it easy to identify the distribution of tasks throughout the specified time range.

### Usage:

```bash
php artisan schedule:calendar --display=dot
```

![Single day with dots](https://github.com/inDeev/Laravel-Schedule-Calendar/blob/main/img/single_day_dot.png)

## Display Option: `--display=list`

The `--display=list` option provides a detailed list of concrete commands for each time piece, offering a comprehensive view of your scheduled activities.

### Usage:

```bash
php artisan schedule:calendar --display=list
```

![Single day list](https://github.com/inDeev/Laravel-Schedule-Calendar/blob/main/img/single_day_list.png)

## Range Option: `--range=week`

The `--range=week` option allows you to view scheduled tasks for the week around a specified day (or current day as default), providing a broader context of your upcoming activities.

### Usage:

```bash
php artisan schedule:calendar --range=week
```

![Week count](https://github.com/inDeev/Laravel-Schedule-Calendar/blob/main/img/week_count.png)

## Date Selection Option: `--day=YYYY-MM-DD`

The `--day=YYYY-MM-DD` option allows you to specify a particular date for viewing the scheduled tasks, providing detailed insights into the tasks for that specific day.

### Usage:

```bash
php artisan schedule:calendar --day=yyyy-mm-dd
```

![Single day date](https://github.com/inDeev/Laravel-Schedule-Calendar/blob/main/img/single_day_date.png)

## Hours per line Option: `--hoursPerLine`

The `--hoursPerLine` option allows you to specify how many hours will be displayed per one output line. This parameter provides flexibility in tailoring the visual representation based on your preferences.

### Usage:

```bash
php artisan schedule:calendar --hoursPerLine=6
```

![Single day 6 hours](https://github.com/inDeev/Laravel-Schedule-Calendar/blob/main/img/single_day_6hourPerLine.png)

```bash
php artisan schedule:calendar --hoursPerLine=24
```

![Single day 24 hours](https://github.com/inDeev/Laravel-Schedule-Calendar/blob/main/img/single_day_24hourPerLine.png)

## Contribution

👋 Thank you for considering contributing to our project! We welcome contributions from the community to help make this project even better. Whether you're fixing a bug, improving documentation, or adding a new feature, your efforts are highly appreciated and will be credited.

## Credits

-   [Petr Kateřiňák](https://github.com/indeev)
-   [Jarand](https://github.com/lokeland)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
