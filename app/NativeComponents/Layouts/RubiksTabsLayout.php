<?php

namespace App\NativeComponents\Layouts;

use App\Icons\Android;
use App\Icons\Ios;
use Native\Mobile\Edge\Layouts\Builders\NavBar;
use Native\Mobile\Edge\Layouts\Builders\Tab;
use Native\Mobile\Edge\Layouts\Builders\TabBar;
use Native\Mobile\Edge\Layouts\NativeLayout;
use Native\Mobile\Edge\NativeComponent;

class RubiksTabsLayout extends NativeLayout
{
    public function usesNativeChrome(): bool
    {
        return true;
    }

    public function navBar(NativeComponent $screen): ?NavBar
    {
        $title = method_exists($screen, 'navTitle') ? $screen->navTitle() : 'Rubiks Cube';

        return NavBar::make()->title($title);
    }

    public function tabBar(NativeComponent $screen): ?TabBar
    {
        return TabBar::make()
            ->add(Tab::link('Scan', '/scan', ios: Ios::Camera, android: Android::Camera))
            ->add(Tab::link('Review', '/review', ios: Ios::Grid, android: Android::Apps))
            ->add(Tab::link('Solve', '/solve', ios: Ios::PlayCircle, android: Android::PlayCircleFilled));
    }
}
