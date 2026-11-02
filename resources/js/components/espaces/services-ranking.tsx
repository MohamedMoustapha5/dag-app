type Service = {
    label: string;
    total: number;
    novaTech: boolean;
};

type Props = {
    services: Service[];
};

export default function ServicesRanking({ services }: Props) {
    const max = Math.max(...services.map((service) => service.total), 1);

    if (services.length === 0) {
        return (
            <p className="muted" style={{ fontSize: 13 }}>
                Aucune demande ce mois-ci.
            </p>
        );
    }

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
            {services.map((service) => (
                <div key={service.label}>
                    <div
                        style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            fontSize: 13,
                            marginBottom: 6,
                        }}
                    >
                        <span style={{ fontWeight: 600 }}>
                            {service.label}
                            {service.novaTech && (
                                <span
                                    className="chip chip-orange"
                                    style={{ marginLeft: 8 }}
                                >
                                    Nova-Tech
                                </span>
                            )}
                        </span>
                        <strong>{service.total}</strong>
                    </div>
                    <div
                        style={{
                            height: 8,
                            borderRadius: 999,
                            background: '#f1f2f6',
                            overflow: 'hidden',
                        }}
                    >
                        <div
                            style={{
                                height: '100%',
                                borderRadius: 999,
                                width: `${(service.total / max) * 100}%`,
                                background: service.novaTech
                                    ? 'var(--dag-orange)'
                                    : 'var(--dag-blue)',
                            }}
                        />
                    </div>
                </div>
            ))}
        </div>
    );
}
