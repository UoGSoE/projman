<?php

namespace App\Enums;

enum StrategicInitiative: string
{
    case INSPIRE = 'Inspire';
    case CREATE = 'Create';
    case THRIVE = 'Thrive';
    case INVEST = 'Invest';

    public function label(): string
    {
        return $this->value;
    }

    public function description(): string
    {
        return match ($this) {
            self::INSPIRE => 'We will create self-sustaining peer support networks and communities of practice to further grow our innovation community, including an “Innovation 101” programme, the Women Researchers Enterprise Network (WREN), the RISE Founders Club, Investor Days, and the University’s KE & Innovation Awards to recognise our top innovators.',
            self::CREATE => 'We will pump-prime our pipeline via targeted strategic funding sources, including the MedTech Innovation Fund, the Creative Launch Fund, the Social Innovation Fund, and a range of College-specific initiatives, including innovation audits.',
            self::THRIVE => 'We will support our developing ventures and de-risk our innovations via structured accelerator style support, including the UofG Founders Fund, ICURe, the Infinity G Venture Builder programme (open to externals) and beLAB1407.',
            self::INVEST => 'We will sustain our spinouts to the next stage of their commercialisation journey through targeted strategic investment in companies directly and growth in the operational capabilities of our holdings company, GUHL.',
        };
    }
}
