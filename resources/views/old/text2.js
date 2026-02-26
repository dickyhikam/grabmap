(async () => {
    try {
        figma.notify("Generating Dashboard (Strict Fix)...");

        // 1. Load Fonts
        await figma.loadFontAsync({ family: "Inter", style: "Regular" });
        await figma.loadFontAsync({ family: "Inter", style: "Medium" });
        await figma.loadFontAsync({ family: "Inter", style: "Bold" });

        // --- COLOR PALETTE ---
        const colors = {
            flagBlue: { r: 0.004, g: 0.125, b: 0.412 }, // #012069
            flagRed: { r: 0.784, g: 0.063, b: 0.18 }, // #c8102e
            teal: { r: 0.361, g: 0.722, b: 0.698 }, // #5cb8b2
            orange: { r: 0.878, g: 0.49, b: 0.224 }, // #e07d39
            lightBlue: { r: 0.251, g: 0.494, b: 0.788 }, // #407ec9
            bgBody: { r: 0.96, g: 0.96, b: 0.98 },
            white: { r: 1, g: 1, b: 1 },
            textMuted: { r: 0.6, g: 0.6, b: 0.7 },
        };

        // --- ICONS ---
        const icons = {
            pager: `<svg width="24" height="24" viewBox="0 0 512 512" fill="none"><path d="M448 96C448 60.7 419.3 32 384 32H64C28.7 32 0 60.7 0 96V416C0 451.3 28.7 480 64 480H384C419.3 480 448 451.3 448 416V96ZM320 160H128C110.3 160 96 145.7 96 128C96 110.3 110.3 96 128 96H320C337.7 96 352 110.3 352 128C352 145.7 337.7 160 320 160ZM192 384H128C110.3 384 96 369.7 96 352V320C96 302.3 110.3 288 128 288H192C209.7 288 224 302.3 224 320V352C224 369.7 209.7 384 192 384ZM320 384H256C238.3 384 224 369.7 224 352V320C224 302.3 238.3 288 256 288H320C337.7 288 352 302.3 352 320V352C352 369.7 337.7 384 320 384Z" fill="white"/></svg>`,
            home: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>`,
            chart: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>`,
            user: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>`,
        };

        // 2. Setup Page
        const pageFrame = figma.createFrame();
        pageFrame.name = "Dashboard - Color Guidelines";
        pageFrame.resize(1440, 1024);
        pageFrame.fills = [{ type: "SOLID", color: colors.bgBody }];
        const viewport = figma.viewport.center;
        pageFrame.x = viewport.x;
        pageFrame.y = viewport.y;

        // --- ROOT LAYOUT ---
        const rootLayout = figma.createFrame();
        rootLayout.name = "Root Layout";
        rootLayout.layoutMode = "HORIZONTAL";
        rootLayout.resize(1440, 1024);
        rootLayout.fills = [];
        pageFrame.appendChild(rootLayout);

        // --- SIDEBAR ---
        const sidebar = figma.createFrame();
        sidebar.name = "Sidebar";
        sidebar.layoutMode = "VERTICAL";
        sidebar.resize(260, 1024);
        sidebar.fills = [{ type: "SOLID", color: colors.flagBlue }];
        sidebar.paddingLeft = 24;
        sidebar.paddingRight = 24;
        sidebar.paddingTop = 40;
        sidebar.itemSpacing = 40;

        rootLayout.appendChild(sidebar);
        sidebar.layoutSizingVertical = "FILL"; // Safe: appended to rootLayout

        // Logo
        const logoText = figma.createText();
        logoText.characters = "BRAND IDENTITY";
        logoText.fontSize = 20;
        logoText.fontName = { family: "Inter", style: "Bold" };
        logoText.fills = [{ type: "SOLID", color: colors.white }];
        sidebar.appendChild(logoText);

        // Menu
        const menuContainer = figma.createFrame();
        menuContainer.layoutMode = "VERTICAL";
        menuContainer.itemSpacing = 8;
        menuContainer.fills = [];
        sidebar.appendChild(menuContainer);
        menuContainer.layoutSizingHorizontal = "FILL"; // Safe: appended to sidebar

        function createMenuItem(label, iconSvg, active = false) {
            const item = figma.createFrame();
            item.layoutMode = "HORIZONTAL";
            item.itemSpacing = 16;
            item.counterAxisAlignItems = "CENTER";
            item.paddingLeft = 16;
            item.paddingRight = 16;
            item.paddingTop = 12;
            item.paddingBottom = 12;
            item.cornerRadius = 8;
            item.fills = active
                ? [{ type: "SOLID", color: colors.lightBlue, opacity: 0.3 }]
                : [];

            const iconNode = figma.createNodeFromSvg(iconSvg);
            iconNode.resize(20, 20);
            const textNode = figma.createText();
            textNode.characters = label;
            textNode.fontSize = 14;
            textNode.fontName = {
                family: "Inter",
                style: active ? "Bold" : "Medium",
            };
            textNode.fills = [{ type: "SOLID", color: colors.white }];

            item.appendChild(iconNode);
            item.appendChild(textNode);
            return item;
        }

        const m1 = createMenuItem("Dashboard", icons.home, false);
        menuContainer.appendChild(m1);
        m1.layoutSizingHorizontal = "FILL";

        const m2 = createMenuItem("Pager System", icons.pager, true);
        menuContainer.appendChild(m2);
        m2.layoutSizingHorizontal = "FILL";

        const m3 = createMenuItem("Analytics", icons.chart, false);
        menuContainer.appendChild(m3);
        m3.layoutSizingHorizontal = "FILL";

        // --- MAIN CONTENT ---
        const mainContent = figma.createFrame();
        mainContent.name = "Content Area";
        mainContent.layoutMode = "VERTICAL";
        mainContent.fills = [];
        rootLayout.appendChild(mainContent);
        mainContent.layoutSizingHorizontal = "FILL"; // Safe
        mainContent.layoutSizingVertical = "FILL"; // Safe

        // Header
        const header = figma.createFrame();
        header.layoutMode = "HORIZONTAL";
        header.primaryAxisAlignItems = "SPACE_BETWEEN";
        header.counterAxisAlignItems = "CENTER";
        header.paddingLeft = 40;
        header.paddingRight = 40;
        header.resize(1180, 80);
        header.fills = [{ type: "SOLID", color: colors.white }];

        const title = figma.createText();
        title.characters = "Pager System Overview";
        title.fontSize = 24;
        title.fontName = { family: "Inter", style: "Bold" };
        title.fills = [{ type: "SOLID", color: colors.flagBlue }];
        const profile = figma.createEllipse();
        profile.resize(40, 40);
        profile.fills = [{ type: "SOLID", color: colors.flagRed }];

        header.appendChild(title);
        header.appendChild(profile);
        mainContent.appendChild(header);
        header.layoutSizingHorizontal = "FILL"; // Safe

        // Body
        const body = figma.createFrame();
        body.layoutMode = "VERTICAL";
        body.paddingLeft = 40;
        body.paddingRight = 40;
        body.paddingTop = 40;
        body.itemSpacing = 30;
        body.fills = [];
        mainContent.appendChild(body);
        body.layoutSizingHorizontal = "FILL";
        body.layoutSizingVertical = "FILL";

        // Stats Row
        const statsRow = figma.createFrame();
        statsRow.layoutMode = "HORIZONTAL";
        statsRow.itemSpacing = 24;
        statsRow.fills = [];
        body.appendChild(statsRow);
        statsRow.layoutSizingHorizontal = "FILL";

        function createStatCard(label, value, color, iconSvg) {
            const card = figma.createFrame();
            card.layoutMode = "VERTICAL";
            card.paddingLeft = 24;
            card.paddingRight = 24;
            card.paddingTop = 24;
            card.paddingBottom = 24;
            card.itemSpacing = 12;
            card.cornerRadius = 12;
            card.fills = [{ type: "SOLID", color: colors.white }];

            // Top Row (Icon)
            const topRow = figma.createFrame();
            topRow.layoutMode = "HORIZONTAL";
            topRow.primaryAxisAlignItems = "SPACE_BETWEEN";

            const iconBg = figma.createFrame();
            iconBg.resize(40, 40);
            iconBg.cornerRadius = 20;
            iconBg.fills = [{ type: "SOLID", color: color, opacity: 0.15 }];
            iconBg.layoutMode = "HORIZONTAL";
            iconBg.primaryAxisAlignItems = "CENTER";
            iconBg.counterAxisAlignItems = "CENTER";
            const ic = figma.createNodeFromSvg(iconSvg);
            ic.resize(20, 20);
            ic.children.forEach((child) => {
                if (child.type === "VECTOR") {
                    child.fills = [{ type: "SOLID", color: color }];
                    child.strokes = [];
                }
            });
            iconBg.appendChild(ic);

            topRow.appendChild(iconBg);

            // IMPORTANT: Append topRow to card BEFORE setting layoutSizing
            card.appendChild(topRow);
            topRow.layoutSizingHorizontal = "FILL";

            const v = figma.createText();
            v.characters = value;
            v.fontSize = 28;
            v.fontName = { family: "Inter", style: "Bold" };
            v.fills = [{ type: "SOLID", color: colors.flagBlue }];
            const l = figma.createText();
            l.characters = label;
            l.fontSize = 14;
            l.fills = [{ type: "SOLID", color: colors.textMuted }];

            card.appendChild(v);
            card.appendChild(l);

            // Accent Line
            const accent = figma.createRectangle();
            accent.resize(200, 4);
            accent.fills = [{ type: "SOLID", color: color }];
            card.appendChild(accent);
            accent.layoutPositioning = "ABSOLUTE";
            accent.y = 0;
            accent.x = 0;
            accent.constraints = { horizontal: "SCALE", vertical: "MIN" };
            accent.resize(100, 4);

            return card;
        }

        const c1 = createStatCard(
            "Active Pagers",
            "1,240",
            colors.teal,
            icons.pager
        );
        statsRow.appendChild(c1);
        c1.layoutSizingHorizontal = "FILL";

        const c2 = createStatCard(
            "Pending Alerts",
            "56",
            colors.orange,
            icons.chart
        );
        statsRow.appendChild(c2);
        c2.layoutSizingHorizontal = "FILL";

        const c3 = createStatCard(
            "Total Users",
            "890",
            colors.lightBlue,
            icons.user
        );
        statsRow.appendChild(c3);
        c3.layoutSizingHorizontal = "FILL";

        const c4 = createStatCard(
            "Critical Issues",
            "3",
            colors.flagRed,
            icons.chart
        );
        statsRow.appendChild(c4);
        c4.layoutSizingHorizontal = "FILL";

        // --- CHART SECTION (The Source of previous error) ---
        const chartSection = figma.createFrame();
        chartSection.layoutMode = "VERTICAL";
        chartSection.cornerRadius = 12;
        chartSection.paddingLeft = 30;
        chartSection.paddingRight = 30;
        chartSection.paddingTop = 30;
        chartSection.paddingBottom = 30;
        chartSection.fills = [{ type: "SOLID", color: colors.white }];
        chartSection.resize(100, 400);

        const chartHeader = figma.createText();
        chartHeader.characters = "Activity Overview";
        chartHeader.fontSize = 18;
        chartHeader.fontName = { family: "Inter", style: "Bold" };
        chartHeader.fills = [{ type: "SOLID", color: colors.flagBlue }];
        chartSection.appendChild(chartHeader);

        const barContainer = figma.createFrame();
        barContainer.layoutMode = "HORIZONTAL";
        barContainer.primaryAxisAlignItems = "SPACE_BETWEEN";
        barContainer.resize(100, 300);
        barContainer.fills = [];

        // Append barContainer first!
        chartSection.appendChild(barContainer);

        // NOW we can set Fill
        chartSection.layoutSizingHorizontal = "FILL"; // inside body
        barContainer.layoutSizingHorizontal = "FILL"; // inside chartSection

        const palette = [
            colors.teal,
            colors.orange,
            colors.lightBlue,
            colors.flagRed,
            colors.flagBlue,
        ];

        for (let i = 0; i < 12; i++) {
            const barWrap = figma.createFrame();
            barWrap.layoutMode = "VERTICAL";
            barWrap.primaryAxisAlignItems = "MAX";
            barWrap.counterAxisAlignItems = "CENTER";
            barWrap.width = 40;

            const bar = figma.createRectangle();
            const h = Math.floor(Math.random() * 200) + 50;
            bar.resize(30, h);
            bar.cornerRadius = 4;
            bar.fills = [{ type: "SOLID", color: palette[i % palette.length] }];

            barWrap.appendChild(bar);

            // STRICT ORDER FIX HERE:
            // 1. Append barWrap to barContainer
            barContainer.appendChild(barWrap);

            // 2. NOW set layoutSizingVertical to FILL
            barWrap.layoutSizingVertical = "FILL";
        }

        // Finally attach chart section to body
        body.appendChild(chartSection);

        figma.currentPage.appendChild(pageFrame);
        figma.viewport.scrollAndZoomIntoView([pageFrame]);
    } catch (error) {
        console.error(error);
        figma.notify("Error: " + error.message);
    }
})();
