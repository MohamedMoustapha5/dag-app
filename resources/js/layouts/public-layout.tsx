import type { PropsWithChildren } from 'react';
import ContactFab from '@/components/public/contact-fab';
import CookieBanner from '@/components/public/cookie-banner';
import SiteFooter from '@/components/public/site-footer';
import TopBar from '@/components/public/topbar';
import { useScrollReveal } from '@/hooks/use-scroll-reveal';

export default function PublicLayout({ children }: PropsWithChildren) {
    useScrollReveal();

    return (
        <div className="dag">
            <TopBar />
            <main>{children}</main>
            <SiteFooter />
            <ContactFab />
            <CookieBanner />
        </div>
    );
}
