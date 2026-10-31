type Segment = {
    label: string;
    value: number;
    color: string;
};

type Props = {
    segments: Segment[];
};

const RADIUS = 52;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;

export default function RepartitionDonut({ segments }: Props) {
    const total = segments.reduce((sum, segment) => sum + segment.value, 0);

    let offset = 0;

    return (
        <div
            style={{
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                gap: 20,
            }}
        >
            <div style={{ position: 'relative', width: 140, height: 140 }}>
                <svg viewBox="0 0 120 120" width={140} height={140}>
                    <circle
                        cx={60}
                        cy={60}
                        r={RADIUS}
                        fill="none"
                        stroke="#f1f2f6"
                        strokeWidth={14}
                    />
                    {total > 0 &&
                        segments
                            .filter((segment) => segment.value > 0)
                            .map((segment) => {
                                const length =
                                    (segment.value / total) * CIRCUMFERENCE;
                                const dashoffset = CIRCUMFERENCE - offset;
                                offset += length;

                                return (
                                    <circle
                                        key={segment.label}
                                        cx={60}
                                        cy={60}
                                        r={RADIUS}
                                        fill="none"
                                        stroke={segment.color}
                                        strokeWidth={14}
                                        strokeDasharray={`${length} ${CIRCUMFERENCE - length}`}
                                        strokeDashoffset={dashoffset}
                                        transform="rotate(-90 60 60)"
                                        strokeLinecap="butt"
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
                    }}
                >
                    <strong
                        style={{
                            fontSize: 22,
                            fontWeight: 800,
                            color: 'var(--dag-navy)',
                        }}
                    >
                        {total}
                    </strong>
                    <span className="muted" style={{ fontSize: 11 }}>
                        candidatures
                    </span>
                </div>
            </div>

            <div
                style={{
                    width: '100%',
                    display: 'flex',
                    flexDirection: 'column',
                    gap: 8,
                }}
            >
                {segments.map((segment) => (
                    <div
                        key={segment.label}
                        style={{
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between',
                            fontSize: 12.5,
                        }}
                    >
                        <span
                            style={{
                                display: 'flex',
                                alignItems: 'center',
                                gap: 8,
                            }}
                        >
                            <span
                                style={{
                                    width: 9,
                                    height: 9,
                                    borderRadius: '50%',
                                    background: segment.color,
                                    display: 'inline-block',
                                }}
                            />
                            {segment.label}
                        </span>
                        <strong>{segment.value}</strong>
                    </div>
                ))}
            </div>
        </div>
    );
}
