import { useState } from 'react';

type Point = {
    label: string;
    montant: number;
};

type Props = {
    series: Point[];
};

type Coord = { x: number; y: number };

const WIDTH = 600;
const HEIGHT = 220;
const PADDING_X = 10;
const PADDING_TOP = 20;
const PADDING_BOTTOM = 30;
const GRID_LINES = 4;

/**
 * Smooths a polyline into a cubic-bezier path through every point, using
 * each point's neighbours to derive its control points (Catmull-Rom-style
 * smoothing). Keeps the chart dependency-free, matching the rest of the
 * espace dashboards' hand-built SVG components.
 */
function smoothPath(points: Coord[]): string {
    if (points.length === 0) {
        return '';
    }
    if (points.length === 1) {
        return `M ${points[0].x} ${points[0].y}`;
    }

    const controlPoint = (
        current: Coord,
        previous: Coord | undefined,
        next: Coord | undefined,
        reverse: boolean,
    ): Coord => {
        const p = previous ?? current;
        const n = next ?? current;
        const smoothing = 0.2;
        const lengthX = n.x - p.x;
        const lengthY = n.y - p.y;
        const angle = Math.atan2(lengthY, lengthX) + (reverse ? Math.PI : 0);
        const length = Math.sqrt(lengthX ** 2 + lengthY ** 2) * smoothing;

        return {
            x: current.x + Math.cos(angle) * length,
            y: current.y + Math.sin(angle) * length,
        };
    };

    return points.reduce((acc, point, index, all) => {
        if (index === 0) {
            return `M ${point.x} ${point.y}`;
        }

        const start = controlPoint(
            all[index - 1],
            all[index - 2],
            point,
            false,
        );
        const end = controlPoint(point, all[index - 1], all[index + 1], true);

        return `${acc} C ${start.x} ${start.y} ${end.x} ${end.y} ${point.x} ${point.y}`;
    }, '');
}

export default function PaiementsChart({ series }: Props) {
    const [hoverIndex, setHoverIndex] = useState<number | null>(null);

    const max = Math.max(...series.map((point) => point.montant), 1);
    const innerWidth = WIDTH - PADDING_X * 2;
    const innerHeight = HEIGHT - PADDING_TOP - PADDING_BOTTOM;
    const step = series.length > 1 ? innerWidth / (series.length - 1) : 0;

    const coords: Coord[] = series.map((point, index) => ({
        x: PADDING_X + step * index,
        y: PADDING_TOP + innerHeight - (point.montant / max) * innerHeight,
    }));

    const linePath = smoothPath(coords);

    const areaPath =
        coords.length > 0
            ? `${linePath} L ${coords[coords.length - 1].x} ${PADDING_TOP + innerHeight} L ${coords[0].x} ${PADDING_TOP + innerHeight} Z`
            : '';

    const gridY = Array.from(
        { length: GRID_LINES + 1 },
        (_, index) => PADDING_TOP + (innerHeight / GRID_LINES) * index,
    );

    const formatMontant = (montant: number) =>
        new Intl.NumberFormat('fr-FR').format(montant);

    const hovered = hoverIndex !== null ? coords[hoverIndex] : null;

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
                            stopOpacity={0.2}
                        />
                        <stop
                            offset="100%"
                            stopColor="var(--dag-blue)"
                            stopOpacity={0}
                        />
                    </linearGradient>
                </defs>

                {gridY.map((y) => (
                    <line
                        key={y}
                        x1={PADDING_X}
                        y1={y}
                        x2={WIDTH - PADDING_X}
                        y2={y}
                        stroke="#eef0f5"
                        strokeWidth={1}
                    />
                ))}

                {areaPath && <path d={areaPath} fill="url(#paiements-fill)" />}
                {linePath && (
                    <path
                        d={linePath}
                        fill="none"
                        stroke="var(--dag-blue)"
                        strokeWidth={2.75}
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
                            r={hoverIndex === index ? 6 : 5}
                            fill="#fff"
                            stroke="var(--dag-blue)"
                            strokeWidth={hoverIndex === index ? 3 : 2.25}
                        />
                        <text
                            x={coord.x}
                            y={HEIGHT - 10}
                            fontSize={11}
                            fill="var(--dag-text-muted)"
                            textAnchor="middle"
                        >
                            {series[index].label}
                        </text>
                    </g>
                ))}
            </svg>

            {hovered && hoverIndex !== null && (
                <div
                    style={{
                        position: 'absolute',
                        left: `${(hovered.x / WIDTH) * 100}%`,
                        top: `${(hovered.y / HEIGHT) * 100}%`,
                        transform: 'translate(-50%, calc(-100% - 14px))',
                        pointerEvents: 'none',
                    }}
                >
                    <div
                        style={{
                            background: 'var(--dag-navy)',
                            color: '#fff',
                            fontSize: 12.5,
                            fontWeight: 700,
                            padding: '7px 12px',
                            borderRadius: 8,
                            whiteSpace: 'nowrap',
                            boxShadow: '0 8px 20px rgba(7, 31, 65, 0.25)',
                        }}
                    >
                        {series[hoverIndex].label} ·{' '}
                        {formatMontant(series[hoverIndex].montant)} FCFA
                    </div>
                    <div
                        style={{
                            width: 9,
                            height: 9,
                            background: 'var(--dag-navy)',
                            margin: '-5px auto 0',
                            transform: 'rotate(45deg)',
                            borderRadius: 2,
                        }}
                    />
                </div>
            )}
        </div>
    );
}
