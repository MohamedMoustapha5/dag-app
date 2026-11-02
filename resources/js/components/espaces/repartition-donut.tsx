type Segment = {
    label: string;
    value: number;
    color: string;
};

type Props = {
    segments: Segment[];
    centerLabel?: string;
    centerValue?: string;
    legendAsPercentage?: boolean;
};

const RADIUS = 52;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;
const GAP = 5;

export default function RepartitionDonut({
    segments,
    centerLabel = 'candidatures',
    centerValue,
    legendAsPercentage = false,
}: Props) {
    const total = segments.reduce((sum, segment) => sum + segment.value, 0);
    const isEmpty = total === 0;

    let offset = 0;

    return (
        <div
            style={{
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                gap: 22,
            }}
        >
            <div style={{ position: 'relative', width: 150, height: 150 }}>
                <svg viewBox="0 0 120 120" width={150} height={150}>
                    <circle
                        cx={60}
                        cy={60}
                        r={RADIUS}
                        fill="none"
                        stroke="#eef0f5"
                        strokeWidth={14}
                        strokeDasharray={isEmpty ? '1 6' : undefined}
                        strokeLinecap="round"
                    />
                    {!isEmpty &&
                        segments
                            .filter((segment) => segment.value > 0)
                            .map((segment) => {
                                const fullLength =
                                    (segment.value / total) * CIRCUMFERENCE;
                                const drawLength = Math.max(
                                    fullLength - GAP,
                                    0,
                                );
                                const dashoffset =
                                    CIRCUMFERENCE - offset - GAP / 2;
                                offset += fullLength;

                                return (
                                    <circle
                                        key={segment.label}
                                        cx={60}
                                        cy={60}
                                        r={RADIUS}
                                        fill="none"
                                        stroke={segment.color}
                                        strokeWidth={14}
                                        strokeDasharray={`${drawLength} ${CIRCUMFERENCE - drawLength}`}
                                        strokeDashoffset={dashoffset}
                                        transform="rotate(-90 60 60)"
                                        strokeLinecap="round"
                                        style={{
                                            filter: 'drop-shadow(0 2px 4px rgba(7, 31, 65, 0.12))',
                                        }}
                                    />
                                );
                            })}
                </svg>
                <div
                    style={{
                        position: 'absolute',
                        inset: 0,
                        display: 'flex',
                        flexDirection: 'column',
                        alignItems: 'center',
                        justifyContent: 'center',
                        padding: '0 18px',
                    }}
                >
                    {isEmpty ? (
                        <span
                            className="muted"
                            style={{
                                fontSize: 12,
                                textAlign: 'center',
                                lineHeight: 1.4,
                            }}
                        >
                            Aucune donnée
                            <br />
                            pour le moment
                        </span>
                    ) : (
                        <>
                            <strong
                                style={{
                                    fontSize: centerValue ? 17 : 22,
                                    fontWeight: 800,
                                    color: 'var(--dag-navy)',
                                    textAlign: 'center',
                                }}
                            >
                                {centerValue ?? total}
                            </strong>
                            <span className="muted" style={{ fontSize: 11 }}>
                                {centerLabel}
                            </span>
                        </>
                    )}
                </div>
            </div>

            <div
                style={{
                    width: '100%',
                    display: 'flex',
                    flexDirection: 'column',
                }}
            >
                {segments.map((segment, index) => (
                    <div
                        key={segment.label}
                        style={{
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between',
                            fontSize: 13,
                            padding: '10px 2px',
                            borderTop:
                                index > 0
                                    ? '1px solid var(--dag-border)'
                                    : 'none',
                        }}
                    >
                        <span
                            style={{
                                display: 'flex',
                                alignItems: 'center',
                                gap: 10,
                                fontWeight: 600,
                                color: isEmpty
                                    ? 'var(--dag-text-muted)'
                                    : 'var(--dag-navy)',
                            }}
                        >
                            <span
                                style={{
                                    width: 10,
                                    height: 10,
                                    borderRadius: '50%',
                                    background: segment.color,
                                    display: 'inline-block',
                                    opacity: isEmpty ? 0.4 : 1,
                                    boxShadow: isEmpty
                                        ? 'none'
                                        : `0 0 0 3px color-mix(in srgb, ${segment.color} 16%, transparent)`,
                                }}
                            />
                            {segment.label}
                        </span>
                        <strong
                            style={{
                                fontSize: 12.5,
                                fontWeight: 700,
                                padding: '3px 9px',
                                borderRadius: 999,
                                color: isEmpty
                                    ? 'var(--dag-text-muted)'
                                    : segment.color,
                                background: isEmpty
                                    ? '#f1f2f6'
                                    : `color-mix(in srgb, ${segment.color} 14%, white)`,
                            }}
                        >
                            {legendAsPercentage
                                ? `${total > 0 ? Math.round((segment.value / total) * 100) : 0}%`
                                : segment.value}
                        </strong>
                    </div>
                ))}
            </div>
        </div>
    );
}
