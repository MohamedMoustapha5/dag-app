import { useState } from 'react';

type Point = {
    label: string;
    montant: number;
};

type Props = {
    series: Point[];
};

const WIDTH = 600;
const HEIGHT = 200;
const PADDING_X = 8;
const PADDING_TOP = 16;
const PADDING_BOTTOM = 28;

export default function PaiementsChart({ series }: Props) {
    const [hoverIndex, setHoverIndex] = useState<number | null>(null);

    const max = Math.max(...series.map((point) => point.montant), 1);
    const innerWidth = WIDTH - PADDING_X * 2;
    const innerHeight = HEIGHT - PADDING_TOP - PADDING_BOTTOM;
    const step = series.length > 1 ? innerWidth / (series.length - 1) : 0;

    const coords = series.map((point, index) => ({
        x: PADDING_X + step * index,
        y: PADDING_TOP + innerHeight - (point.montant / max) * innerHeight,
    }));

    const linePath = coords
        .map(
            (coord, index) =>
                `${index === 0 ? 'M' : 'L'} ${coord.x} ${coord.y}`,
        )
        .join(' ');

    const areaPath =
        coords.length > 0
            ? `${linePath} L ${coords[coords.length - 1].x} ${PADDING_TOP + innerHeight} L ${coords[0].x} ${PADDING_TOP + innerHeight} Z`
            : '';

    const formatMontant = (montant: number) =>
        new Intl.NumberFormat('fr-FR').format(montant);

    return (
        <div style={{ position: 'relative' }}>
            <svg
                viewBox={`0 0 ${WIDTH} ${HEIGHT}`}
                width="100%"
                height={HEIGHT}
                preserveAspectRatio="none"
                onMouseLeave={() => setHoverIndex(null)}
            >
                <defs>
                    <linearGradient
                        id="paiements-fill"
                        x1="0"
                        y1="0"
                        x2="0"
                        y2="1"
                    >
                        <stop
                            offset="0%"
                            stopColor="var(--dag-blue)"
                            stopOpacity={0.22}
                        />
                        <stop
                            offset="100%"
                            stopColor="var(--dag-blue)"
                            stopOpacity={0}
                        />
                    </linearGradient>
                </defs>

                {areaPath && <path d={areaPath} fill="url(#paiements-fill)" />}
                {linePath && (
                    <path
                        d={linePath}
                        fill="none"
                        stroke="var(--dag-blue)"
                        strokeWidth={2.5}
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                )}

                {coords.map((coord, index) => (
                    <g key={series[index].label + index}>
                        <rect
                            x={coord.x - step / 2}
                            y={0}
                            width={step || innerWidth}
                            height={HEIGHT}
                            fill="transparent"
                            onMouseEnter={() => setHoverIndex(index)}
                        />
                        {hoverIndex === index && (
                            <line
                                x1={coord.x}
                                y1={PADDING_TOP}
                                x2={coord.x}
                                y2={PADDING_TOP + innerHeight}
                                stroke="var(--dag-border)"
                                strokeDasharray="3 3"
                            />
                        )}
                        <circle
                            cx={coord.x}
                            cy={coord.y}
                            r={hoverIndex === index ? 4.5 : 2.5}
                            fill="var(--dag-blue)"
                        />
                        <text
                            x={coord.x}
                            y={HEIGHT - 8}
                            fontSize={10}
                            fill="var(--dag-text-muted)"
                            textAnchor="middle"
                        >
                            {series[index].label}
                        </text>
                    </g>
                ))}
            </svg>

            {hoverIndex !== null && (
                <div
                    style={{
                        position: 'absolute',
                        left: `${(coords[hoverIndex].x / WIDTH) * 100}%`,
                        top: 0,
                        transform: 'translate(-50%, -100%)',
                        background: 'var(--dag-navy)',
                        color: '#fff',
                        fontSize: 12,
                        fontWeight: 700,
                        padding: '6px 10px',
                        borderRadius: 8,
                        whiteSpace: 'nowrap',
                        pointerEvents: 'none',
                    }}
                >
                    {series[hoverIndex].label} —{' '}
                    {formatMontant(series[hoverIndex].montant)} FCFA
                </div>
            )}
        </div>
    );
}
