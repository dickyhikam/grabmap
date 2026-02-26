(async () => {
    // 1. Load Fonts
    await figma.loadFontAsync({ family: "Inter", style: "Regular" });
    await figma.loadFontAsync({ family: "Inter", style: "Medium" });
    await figma.loadFontAsync({ family: "Inter", style: "Bold" });

    const targetFrame = figma.currentPage.findOne(
        (n) => n.name.toLowerCase() === "dashboard" && n.type === "FRAME"
    );

    if (!targetFrame) {
        figma.notify("Frame 'dashboard' tidak ditemukan!");
        return;
    }

    // --- CONFIG WARNA (CADMUS STYLE) ---
    const primary = { r: 0, g: 0.188, b: 0.341 }; // #003057
    const primaryLight = { r: 0.94, g: 0.96, b: 0.98 };
    const textDark = { r: 0.12, g: 0.13, b: 0.15 };
    const textMuted = { r: 0.55, g: 0.58, b: 0.62 };

    // --- DATA ICON ---
    const icons = {
        dashboard: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>`,
        database: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s 9-1.34 9-3V5"></path></svg>`,
        layout: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>`,
        users: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>`,
        image: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>`,
        eye: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`,
        settingsUser: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>`,
        chevronDown: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"></path></svg>`,
        dot: `<svg width="6" height="6" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="12"></circle></svg>`,
    };

    const sidebar = figma.createFrame();
    sidebar.name = "Modern Sidebar Dropdown";
    sidebar.layoutMode = "VERTICAL";
    sidebar.resize(280, targetFrame.height);
    sidebar.fills = [{ type: "SOLID", color: { r: 1, g: 1, b: 1 } }];
    sidebar.paddingLeft = 24;
    sidebar.paddingRight = 24;
    sidebar.paddingTop = 40;
    sidebar.itemSpacing = 4;

    // Logo
    const logo = figma.createText();
    logo.characters = "CADMUS";
    logo.fontName = { family: "Inter", style: "Bold" };
    logo.fontSize = 22;
    logo.fills = [{ type: "SOLID", color: primary }];
    sidebar.appendChild(logo);

    // Spacer
    const spacer = figma.createFrame();
    spacer.resize(10, 20);
    spacer.fills = [];
    sidebar.appendChild(spacer);

    // --- FUNCTION: CREATE MENU ITEM ---
    function createMenuItem(
        label,
        iconKey,
        isActive = false,
        hasDropdown = false
    ) {
        const item = figma.createFrame();
        item.name = `Menu / ${label}`;
        item.layoutMode = "HORIZONTAL";
        item.counterAxisAlignItems = "CENTER";
        item.primaryAxisSizingMode = "FIXED";
        item.resize(232, 44);
        item.paddingLeft = 16;
        item.paddingRight = 16;
        item.itemSpacing = 12;
        item.cornerRadius = 10;
        item.fills = isActive ? [{ type: "SOLID", color: primaryLight }] : [];

        const currentColor = isActive ? primary : textMuted;

        // Leading Icon
        const iconNode = figma.createNodeFromSvg(icons[iconKey]);
        iconNode.children.forEach((c) => {
            if ("strokes" in c)
                c.strokes = [{ type: "SOLID", color: currentColor }];
        });

        // Label
        const text = figma.createText();
        text.characters = label;
        text.fontName = {
            family: "Inter",
            style: isActive ? "Medium" : "Regular",
        };
        text.fontSize = 14;
        text.fills = [{ type: "SOLID", color: currentColor }];
        text.layoutGrow = 1; // Biar teks dorong chevron ke kanan

        item.appendChild(iconNode);
        item.appendChild(text);

        // Chevron if dropdown
        if (hasDropdown) {
            const chevron = figma.createNodeFromSvg(icons.chevronDown);
            chevron.children.forEach((c) => {
                if ("strokes" in c)
                    c.strokes = [{ type: "SOLID", color: textMuted }];
            });
            item.appendChild(chevron);
        }

        return item;
    }

    // --- FUNCTION: CREATE SUBMENU ITEM ---
    function createSubMenuItem(label) {
        const item = figma.createFrame();
        item.layoutMode = "HORIZONTAL";
        item.counterAxisAlignItems = "CENTER";
        item.primaryAxisSizingMode = "FIXED";
        item.resize(232, 36);
        item.paddingLeft = 48; // Menjorok ke dalam (Indent)
        item.itemSpacing = 10;
        item.fills = [];

        const dot = figma.createNodeFromSvg(icons.dot);
        dot.children.forEach((c) => {
            if ("fills" in c) c.fills = [{ type: "SOLID", color: textMuted }];
        });

        const text = figma.createText();
        text.characters = label;
        text.fontName = { family: "Inter", style: "Regular" };
        text.fontSize = 13;
        text.fills = [{ type: "SOLID", color: textMuted }];

        item.appendChild(dot);
        item.appendChild(text);
        return item;
    }

    // --- ASSEMBLY ---
    sidebar.appendChild(createMenuItem("Dashboard", "dashboard", true));

    // Dropdown Group
    const dropdownGroup = figma.createFrame();
    dropdownGroup.name = "Dropdown Group";
    dropdownGroup.layoutMode = "VERTICAL";
    dropdownGroup.primaryAxisSizingMode = "AUTO";
    dropdownGroup.counterAxisSizingMode = "AUTO";
    dropdownGroup.fills = [];

    dropdownGroup.appendChild(
        createMenuItem("Team Members", "users", false, true)
    );
    dropdownGroup.appendChild(createSubMenuItem("All Members"));
    dropdownGroup.appendChild(createSubMenuItem("Roles & Permissions"));

    sidebar.appendChild(dropdownGroup);

    // --- MASUKKAN KE FRAME TARGET ---
    targetFrame.appendChild(sidebar);
    sidebar.x = 0;
    sidebar.y = 0;

    figma.viewport.scrollAndZoomIntoView([targetFrame]);
    figma.notify("Sidebar dengan Dropdown berhasil ditambahkan!");
})();

// =======================================================
// Bagian Navbar
// =======================================================
(async () => {
    // 1. Load Fonts
    await figma.loadFontAsync({ family: "Inter", style: "Regular" });
    await figma.loadFontAsync({ family: "Inter", style: "Medium" });
    await figma.loadFontAsync({ family: "Inter", style: "Bold" });

    const targetFrame = figma.currentPage.findOne(
        (n) => n.name.toLowerCase() === "dashboard" && n.type === "FRAME"
    );

    if (!targetFrame) {
        figma.notify("Frame 'dashboard' tidak ditemukan!");
        return;
    }

    // --- CONFIG WARNA (CADMUS PRIMARY THEME) ---
    const primary = { r: 0, g: 0.188, b: 0.341 }; // #003057
    const white = { r: 1, g: 1, b: 1 };
    const textLight = { r: 0.8, g: 0.85, b: 0.9 }; // Biru pucat untuk elemen muted di atas navy

    // --- DATA ICON (SVG) ---
    const icons = {
        search: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>`,
        bell: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>`,
        user: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>`,
        chevronDown: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"></path></svg>`,
    };

    // --- NAVBAR MAIN FRAME (PRIMARY BACKGROUND) ---
    const navbar = figma.createFrame();
    navbar.name = "Top Navbar (Dark)";
    navbar.layoutMode = "HORIZONTAL";
    navbar.resize(targetFrame.width - 280, 72);
    navbar.x = 280;
    navbar.y = 0;

    // Set Background ke Primary Navy
    navbar.fills = [{ type: "SOLID", color: primary }];

    navbar.primaryAxisAlignItems = "SPACE_BETWEEN";
    navbar.counterAxisAlignItems = "CENTER";
    navbar.paddingLeft = 32;
    navbar.paddingRight = 32;

    // --- LEFT SIDE: SEARCH ---
    const searchArea = figma.createFrame();
    searchArea.layoutMode = "HORIZONTAL";
    searchArea.counterAxisAlignItems = "CENTER";
    searchArea.itemSpacing = 12;
    searchArea.fills = [];

    const searchIcon = figma.createNodeFromSvg(icons.search);
    // Ikon jadi Putih
    searchIcon.children.forEach((c) => {
        if ("strokes" in c) c.strokes = [{ type: "SOLID", color: white }];
    });

    const searchText = figma.createText();
    searchText.characters = "Search projects...";
    searchText.fontName = { family: "Inter", style: "Regular" };
    searchText.fontSize = 14;
    searchText.fills = [{ type: "SOLID", color: textLight }];

    searchArea.appendChild(searchIcon);
    searchArea.appendChild(searchText);
    navbar.appendChild(searchArea);

    // --- RIGHT SIDE: ACTIONS ---
    const rightArea = figma.createFrame();
    rightArea.layoutMode = "HORIZONTAL";
    rightArea.counterAxisAlignItems = "CENTER";
    rightArea.itemSpacing = 24;
    rightArea.fills = [];

    // Notification Icon (Putih)
    const bellIcon = figma.createNodeFromSvg(icons.bell);
    bellIcon.children.forEach((c) => {
        if ("strokes" in c) c.strokes = [{ type: "SOLID", color: white }];
    });

    // Profile Section
    const profileContainer = figma.createFrame();
    profileContainer.layoutMode = "HORIZONTAL";
    profileContainer.counterAxisAlignItems = "CENTER";
    profileContainer.itemSpacing = 10;
    profileContainer.fills = [];

    // Circle background (Sedikit lebih terang dari navy agar terlihat)
    const iconCircle = figma.createFrame();
    iconCircle.resize(36, 36);
    iconCircle.cornerRadius = 18;
    iconCircle.fills = [
        { type: "SOLID", color: { r: 1, g: 1, b: 1 }, opacity: 0.1 },
    ];
    iconCircle.layoutMode = "HORIZONTAL";
    iconCircle.primaryAxisAlignItems = "CENTER";
    iconCircle.counterAxisAlignItems = "CENTER";

    const userIcon = figma.createNodeFromSvg(icons.user);
    userIcon.children.forEach((c) => {
        if ("strokes" in c) c.strokes = [{ type: "SOLID", color: white }];
    });
    iconCircle.appendChild(userIcon);

    // Name & Chevron (Putih)
    const nameText = figma.createText();
    nameText.characters = "Administrator";
    nameText.fontName = { family: "Inter", style: "Medium" };
    nameText.fontSize = 14;
    nameText.fills = [{ type: "SOLID", color: white }];

    const chevron = figma.createNodeFromSvg(icons.chevronDown);
    chevron.children.forEach((c) => {
        if ("strokes" in c) c.strokes = [{ type: "SOLID", color: textLight }];
    });

    profileContainer.appendChild(iconCircle);
    profileContainer.appendChild(nameText);
    profileContainer.appendChild(chevron);

    rightArea.appendChild(bellIcon);
    rightArea.appendChild(profileContainer);

    navbar.appendChild(rightArea);

    // --- ASSEMBLY ---
    targetFrame.appendChild(navbar);

    figma.viewport.scrollAndZoomIntoView([navbar]);
    figma.notify("Navbar dengan background Primary berhasil dibuat!");
})();

// =======================================================
// Bagian Content
// =======================================================
(async () => {
    await figma.loadFontAsync({ family: "Inter", style: "Regular" });
    await figma.loadFontAsync({ family: "Inter", style: "Medium" });
    await figma.loadFontAsync({ family: "Inter", style: "Bold" });

    const targetFrame = figma.currentPage.findOne(
        (n) => n.name.toLowerCase() === "dashboard" && n.type === "FRAME"
    );

    if (!targetFrame) {
        figma.notify("Frame 'dashboard' tidak ditemukan!");
        return;
    }

    // --- CONFIG WARNA ---
    const primary = { r: 0, g: 0.188, b: 0.341 }; // Cadmus Navy
    const bgLight = { r: 0.97, g: 0.98, b: 0.99 };
    const textDark = { r: 0.12, g: 0.13, b: 0.15 };
    const textMuted = { r: 0.45, g: 0.5, b: 0.55 };
    const white = { r: 1, g: 1, b: 1 };
    const borderColor = { r: 0.9, g: 0.92, b: 0.94 };

    // --- MAIN CONTENT WRAPPER ---
    const contentArea = figma.createFrame();
    contentArea.name = "CMS Dashboard Content";
    contentArea.layoutMode = "VERTICAL";
    contentArea.resize(targetFrame.width - 280, targetFrame.height - 72);
    contentArea.x = 280;
    contentArea.y = 72;
    contentArea.fills = [{ type: "SOLID", color: bgLight }];
    contentArea.paddingLeft = 40;
    contentArea.paddingRight = 40;
    contentArea.paddingTop = 40;
    contentArea.paddingBottom = 40;
    contentArea.itemSpacing = 24; // Dipersempit sedikit agar lebih kompak

    // --- SECTION 1: HEADER ---
    const header = figma.createFrame();
    header.layoutMode = "HORIZONTAL";
    header.primaryAxisAlignItems = "SPACE_BETWEEN";
    header.counterAxisAlignItems = "CENTER";
    header.primaryAxisSizingMode = "FIXED";
    header.resize(contentArea.width - 80, 60);
    header.fills = [];

    const titleStack = figma.createFrame();
    titleStack.layoutMode = "VERTICAL";
    titleStack.itemSpacing = 4;
    titleStack.fills = [];

    const title = figma.createText();
    title.characters = "Dashboard Overview";
    title.fontName = { family: "Inter", style: "Bold" };
    title.fontSize = 24;
    title.fills = [{ type: "SOLID", color: textDark }];

    const subTitle = figma.createText();
    subTitle.characters = "Monitoring summary of your CMS activities";
    subTitle.fontSize = 14;
    subTitle.fills = [{ type: "SOLID", color: textMuted }];

    titleStack.appendChild(title);
    titleStack.appendChild(subTitle);
    header.appendChild(titleStack);

    const btn = figma.createFrame();
    btn.layoutMode = "HORIZONTAL";
    btn.paddingLeft = 20;
    btn.paddingRight = 20;
    btn.paddingTop = 12;
    btn.paddingBottom = 12;
    btn.cornerRadius = 8;
    btn.fills = [{ type: "SOLID", color: primary }];
    const btnText = figma.createText();
    btnText.characters = "+ Create Post";
    btnText.fontName = { family: "Inter", style: "Medium" };
    btnText.fontSize = 14;
    btnText.fills = [{ type: "SOLID", color: white }];
    btn.appendChild(btnText);
    header.appendChild(btn);

    contentArea.appendChild(header);

    // --- SECTION 2: STAT CARDS ---
    const cardContainer = figma.createFrame();
    cardContainer.layoutMode = "HORIZONTAL";
    cardContainer.itemSpacing = 20;
    cardContainer.fills = [];

    function createStatCard(label, value, info) {
        const card = figma.createFrame();
        card.layoutMode = "VERTICAL";
        card.resize(265, 120);
        card.paddingLeft = 20;
        card.paddingTop = 20;
        card.cornerRadius = 12;
        card.fills = [{ type: "SOLID", color: white }];
        card.strokes = [{ type: "SOLID", color: borderColor }];

        const lbl = figma.createText();
        lbl.characters = label;
        lbl.fontSize = 12;
        lbl.fills = [{ type: "SOLID", color: textMuted }];
        const val = figma.createText();
        val.characters = value;
        val.fontName = { family: "Inter", style: "Bold" };
        val.fontSize = 26;
        val.fills = [{ type: "SOLID", color: textDark }];
        const inf = figma.createText();
        inf.characters = info;
        inf.fontSize = 11;
        inf.fills = [{ type: "SOLID", color: { r: 0.1, g: 0.6, b: 0.3 } }];

        card.appendChild(lbl);
        card.appendChild(val);
        card.appendChild(inf);
        return card;
    }

    cardContainer.appendChild(
        createStatCard("Total Posts", "1,248", "↑ 12 this week")
    );
    cardContainer.appendChild(createStatCard("Published", "1,180", "95% Live"));
    cardContainer.appendChild(createStatCard("Drafts", "68", "Needs review"));
    contentArea.appendChild(cardContainer);

    // --- SECTION 3: TABLE WITH FILTER TABS ---
    const tableSection = figma.createFrame();
    tableSection.name = "Recent Content Section";
    tableSection.layoutMode = "VERTICAL";
    tableSection.resize(contentArea.width - 80, 420);
    tableSection.paddingTop = 24;
    tableSection.cornerRadius = 16;
    tableSection.fills = [{ type: "SOLID", color: white }];
    tableSection.strokes = [{ type: "SOLID", color: borderColor }];
    tableSection.itemSpacing = 16;

    // Filter Tabs Container
    const tabsContainer = figma.createFrame();
    tabsContainer.layoutMode = "HORIZONTAL";
    tabsContainer.itemSpacing = 24;
    tabsContainer.paddingLeft = 24;
    tabsContainer.fills = [];

    function createTab(label, isActive = false) {
        const tab = figma.createFrame();
        tab.layoutMode = "VERTICAL";
        tab.itemSpacing = 8;
        tab.fills = [];

        const tText = figma.createText();
        tText.characters = label;
        tText.fontName = {
            family: "Inter",
            style: isActive ? "Bold" : "Medium",
        };
        tText.fontSize = 14;
        tText.fills = [
            { type: "SOLID", color: isActive ? primary : textMuted },
        ];

        tab.appendChild(tText);

        if (isActive) {
            const underline = figma.createFrame();
            underline.resize(tText.width, 2);
            underline.fills = [{ type: "SOLID", color: primary }];
            tab.appendChild(underline);
        }
        return tab;
    }

    tabsContainer.appendChild(createTab("All Content", true));
    tabsContainer.appendChild(createTab("Published"));
    tabsContainer.appendChild(createTab("Drafts"));
    tabsContainer.appendChild(createTab("Scheduled"));
    tableSection.appendChild(tabsContainer);

    // Header Row Table
    const tableHeader = figma.createFrame();
    tableHeader.layoutMode = "HORIZONTAL";
    tableHeader.resize(tableSection.width, 44);
    tableHeader.paddingLeft = 24;
    tableHeader.counterAxisAlignItems = "CENTER";
    tableHeader.fills = [{ type: "SOLID", color: bgLight }];

    ["ARTICLE TITLE", "CATEGORY", "STATUS", "LAST UPDATED"].forEach((text) => {
        const hText = figma.createText();
        hText.characters = text;
        hText.fontSize = 11;
        hText.fontName = { family: "Inter", style: "Bold" };
        hText.fills = [{ type: "SOLID", color: textMuted }];
        hText.resize(160, hText.height);
        tableHeader.appendChild(hText);
    });
    tableSection.appendChild(tableHeader);

    // Mockup Data Rows
    const data = [
        ["Modern CMS Trends", "Tech", "Published", "2 hours ago"],
        ["Optimizing SEO 2024", "Marketing", "Draft", "5 hours ago"],
        ["Design Systems in UI", "Design", "Published", "1 day ago"],
    ];

    data.forEach((rowItem) => {
        const row = figma.createFrame();
        row.layoutMode = "HORIZONTAL";
        row.resize(tableSection.width, 52);
        row.paddingLeft = 24;
        row.counterAxisAlignItems = "CENTER";
        rowItem.forEach((cell) => {
            const cText = figma.createText();
            cText.characters = cell;
            cText.fontSize = 13;
            cText.fills = [{ type: "SOLID", color: textDark }];
            cText.resize(160, cText.height);
            row.appendChild(cText);
        });
        tableSection.appendChild(row);
    });

    contentArea.appendChild(tableSection);

    // --- ASSEMBLY ---
    targetFrame.appendChild(contentArea);
    figma.viewport.scrollAndZoomIntoView([contentArea]);
    figma.notify("Dashboard CMS dengan Filter Tabs berhasil dibuat!");
})();

(async () => {
    // 1. Load Fonts
    await figma.loadFontAsync({ family: "Inter", style: "Regular" });
    await figma.loadFontAsync({ family: "Inter", style: "Medium" });
    await figma.loadFontAsync({ family: "Inter", style: "Bold" });

    // --- CONFIG WARNA (CADMUS STYLE) ---
    const primary = { r: 0, g: 0.188, b: 0.341 }; // #003057 (Navy)
    const bgLight = { r: 0.97, g: 0.98, b: 0.99 }; // Background Page
    const white = { r: 1, g: 1, b: 1 };
    const textDark = { r: 0.12, g: 0.13, b: 0.15 };
    const textMuted = { r: 0.55, g: 0.58, b: 0.62 };
    const border = { r: 0.9, g: 0.92, b: 0.94 };
    const success = { r: 0.1, g: 0.7, b: 0.4 }; // Green for Published
    const warning = { r: 0.95, g: 0.77, b: 0.05 }; // Yellow for Draft

    // --- ICONS (SVG) ---
    const icons = {
        search: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>`,
        plus: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>`,
        filter: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>`,
        dots: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>`,
        calendar: `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>`,
        user: `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>`,
    };

    const pageFrame = figma.currentPage.findOne(
        (n) => n.name.toLowerCase() === "blog" && n.type === "FRAME"
    );

    if (!pageFrame) {
        figma.notify("Frame 'blog' tidak ditemukan!");
        return;
    }

    // --- 3. SIDEBAR PLACEHOLDER (Kiri) ---
    // Kita buat sidebar statis sederhana agar tidak terlalu panjang kodenya
    const sidebar = figma.createFrame();
    sidebar.name = "Sidebar";
    sidebar.resize(280, 1024);
    sidebar.fills = [{ type: "SOLID", color: white }];
    sidebar.strokes = [{ type: "SOLID", color: border }];
    // Stroke hanya di kanan (Hack: kita tumpuk di layer bawah nanti)

    // Logo Sidebar
    const logo = figma.createText();
    logo.characters = "CADMUS";
    logo.fontName = { family: "Inter", style: "Bold" };
    logo.fontSize = 22;
    logo.x = 24;
    logo.y = 32;
    logo.fills = [{ type: "SOLID", color: primary }];
    sidebar.appendChild(logo);
    pageFrame.appendChild(sidebar);

    // --- 4. NAVBAR PLACEHOLDER (Atas) ---
    const navbar = figma.createFrame();
    navbar.name = "Navbar";
    navbar.resize(1160, 72); // 1440 - 280
    navbar.x = 280;
    navbar.y = 0;
    navbar.fills = [{ type: "SOLID", color: primary }]; // Navy Navbar

    const navTitle = figma.createText();
    navTitle.characters = "Blog Management";
    navTitle.fontName = { family: "Inter", style: "Medium" };
    navTitle.fontSize = 16;
    navTitle.fills = [{ type: "SOLID", color: white }];
    navTitle.x = 32;
    navTitle.y = 26;
    navbar.appendChild(navTitle);
    pageFrame.appendChild(navbar);

    // --- 5. MAIN CONTENT AREA ---
    const content = figma.createFrame();
    content.name = "Content Wrapper";
    content.layoutMode = "VERTICAL";
    content.resize(1080, 900);
    content.x = 280 + 40; // Sidebar + Padding
    content.y = 72 + 40; // Navbar + Padding
    content.itemSpacing = 32;
    content.fills = []; // Transparent

    // --- A. CONTENT HEADER (Title + Actions) ---
    const headerRow = figma.createFrame();
    headerRow.layoutMode = "HORIZONTAL";
    headerRow.primaryAxisAlignItems = "SPACE_BETWEEN";
    headerRow.counterAxisAlignItems = "CENTER";
    headerRow.resize(1080, 50);
    headerRow.fills = [];

    // Title Stack
    const titleStack = figma.createFrame();
    titleStack.layoutMode = "VERTICAL";
    titleStack.itemSpacing = 4;
    titleStack.fills = [];
    const h1 = figma.createText();
    h1.characters = "All Posts";
    h1.fontName = { family: "Inter", style: "Bold" };
    h1.fontSize = 28;
    h1.fills = [{ type: "SOLID", color: textDark }];
    const sub = figma.createText();
    sub.characters = "Manage, edit, and publish your articles.";
    sub.fontSize = 14;
    sub.fills = [{ type: "SOLID", color: textMuted }];
    titleStack.appendChild(h1);
    titleStack.appendChild(sub);
    headerRow.appendChild(titleStack);

    // Action Stack (Search & Button)
    const actions = figma.createFrame();
    actions.layoutMode = "HORIZONTAL";
    actions.itemSpacing = 16;
    actions.fills = [];

    // Search Input
    const searchBox = figma.createFrame();
    searchBox.layoutMode = "HORIZONTAL";
    searchBox.counterAxisAlignItems = "CENTER";
    searchBox.itemSpacing = 8;
    searchBox.paddingLeft = 12;
    searchBox.paddingRight = 12;
    searchBox.resize(250, 40);
    searchBox.cornerRadius = 8;
    searchBox.fills = [{ type: "SOLID", color: white }];
    searchBox.strokes = [{ type: "SOLID", color: border }];

    const searchIcon = figma.createNodeFromSvg(icons.search);
    searchIcon.children[0].strokes = [{ type: "SOLID", color: textMuted }];
    const searchPlace = figma.createText();
    searchPlace.characters = "Search articles...";
    searchPlace.fontSize = 13;
    searchPlace.fills = [{ type: "SOLID", color: textMuted }];
    searchBox.appendChild(searchIcon);
    searchBox.appendChild(searchPlace);

    // Create Button
    const createBtn = figma.createFrame();
    createBtn.layoutMode = "HORIZONTAL";
    createBtn.counterAxisAlignItems = "CENTER";
    createBtn.itemSpacing = 8;
    createBtn.paddingLeft = 16;
    createBtn.paddingRight = 20;
    createBtn.resize(100, 40); // Auto width actually
    createBtn.cornerRadius = 8;
    createBtn.fills = [{ type: "SOLID", color: primary }];

    const plusIcon = figma.createNodeFromSvg(icons.plus);
    plusIcon.children[0].strokes = [{ type: "SOLID", color: white }];
    const btnTxt = figma.createText();
    btnTxt.characters = "Create Post";
    btnTxt.fontName = { family: "Inter", style: "Medium" };
    btnTxt.fontSize = 14;
    btnTxt.fills = [{ type: "SOLID", color: white }];
    createBtn.appendChild(plusIcon);
    createBtn.appendChild(btnTxt);

    actions.appendChild(searchBox);
    actions.appendChild(createBtn);
    headerRow.appendChild(actions);
    content.appendChild(headerRow);

    // --- B. FILTER TABS ---
    const filters = figma.createFrame();
    filters.layoutMode = "HORIZONTAL";
    filters.itemSpacing = 24;
    filters.fills = [];

    ["All (124)", "Published (98)", "Drafts (12)", "Scheduled (4)"].forEach(
        (text, i) => {
            const tab = figma.createFrame();
            tab.layoutMode = "VERTICAL";
            tab.itemSpacing = 8;
            tab.fills = [];

            const txt = figma.createText();
            txt.characters = text;
            txt.fontSize = 14;
            txt.fontName = {
                family: "Inter",
                style: i === 0 ? "Bold" : "Medium",
            };
            txt.fills = [
                { type: "SOLID", color: i === 0 ? primary : textMuted },
            ];

            tab.appendChild(txt);
            if (i === 0) {
                const line = figma.createFrame();
                line.resize(txt.width, 2);
                line.fills = [{ type: "SOLID", color: primary }];
                tab.appendChild(line);
            }
            filters.appendChild(tab);
        }
    );
    content.appendChild(filters);

    // --- C. BLOG GRID (Cards) ---
    const grid = figma.createFrame();
    grid.name = "Blog Grid";
    grid.layoutMode = "HORIZONTAL";
    grid.layoutWrap = "WRAP"; // PENTING: Agar kartu turun ke bawah otomatis
    grid.itemSpacing = 24; // Jarak horizontal
    grid.counterAxisSpacing = 24; // Jarak vertikal
    grid.resize(1080, 600); // Tinggi dinamis
    grid.fills = [];

    // Function to create a Blog Card
    function createBlogCard(titleText, category, status, dateStr) {
        const card = figma.createFrame();
        card.name = "Blog Card";
        card.layoutMode = "VERTICAL";
        card.resize(344, 380); // Lebar fixed untuk 3 kolom (1080 / 3 - gap)
        card.cornerRadius = 12;
        card.fills = [{ type: "SOLID", color: white }];
        card.strokes = [{ type: "SOLID", color: border }];
        card.effects = [
            {
                type: "DROP_SHADOW",
                color: { r: 0, g: 0, b: 0, a: 0.05 },
                offset: { x: 0, y: 4 },
                radius: 12,
                visible: true,
                blendMode: "NORMAL",
            },
        ];

        // 1. Image Thumbnail
        const img = figma.createFrame();
        img.resize(344, 180);
        img.fills = [{ type: "SOLID", color: { r: 0.9, g: 0.92, b: 0.95 } }]; // Placeholder Grey
        // Simulasi icon gambar di tengah
        const imgIcon = figma.createNodeFromSvg(
            `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#B0B5BD" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>`
        );
        imgIcon.x = (344 - 32) / 2;
        imgIcon.y = (180 - 32) / 2;
        img.appendChild(imgIcon);
        card.appendChild(img);

        // 2. Content Container
        const body = figma.createFrame();
        body.layoutMode = "VERTICAL";
        body.paddingLeft = 20;
        body.paddingRight = 20;
        body.paddingTop = 20;
        body.paddingBottom = 20;
        body.itemSpacing = 12;
        body.fills = [];
        body.resize(344, 200);

        // Badge Row
        const badgeRow = figma.createFrame();
        badgeRow.layoutMode = "HORIZONTAL";
        badgeRow.primaryAxisAlignItems = "SPACE_BETWEEN";
        badgeRow.resize(304, 24);
        badgeRow.fills = [];

        // Category Badge
        const catBadge = figma.createFrame();
        catBadge.layoutMode = "HORIZONTAL";
        catBadge.paddingLeft = 10;
        catBadge.paddingRight = 10;
        catBadge.paddingTop = 4;
        catBadge.paddingBottom = 4;
        catBadge.cornerRadius = 4;
        catBadge.fills = [
            { type: "SOLID", color: { r: 0.94, g: 0.96, b: 0.98 } },
        ]; // Light Blue
        const catTxt = figma.createText();
        catTxt.characters = category;
        catTxt.fontSize = 11;
        catTxt.fontName = { family: "Inter", style: "Bold" };
        catTxt.fills = [{ type: "SOLID", color: primary }];
        catBadge.appendChild(catTxt);

        // Status Badge
        const statBadge = figma.createFrame();
        statBadge.layoutMode = "HORIZONTAL";
        statBadge.paddingLeft = 8;
        statBadge.paddingRight = 8;
        statBadge.paddingTop = 4;
        statBadge.paddingBottom = 4;
        statBadge.cornerRadius = 100;
        const isPub = status === "Published";
        statBadge.fills = [
            {
                type: "SOLID",
                color: isPub
                    ? { r: 0.9, g: 1, b: 0.9 }
                    : { r: 1, g: 0.98, b: 0.9 },
            },
        ]; // Light Green or Light Yellow
        const statTxt = figma.createText();
        statTxt.characters = status;
        statTxt.fontSize = 11;
        statTxt.fontName = { family: "Inter", style: "Medium" };
        statTxt.fills = [
            {
                type: "SOLID",
                color: isPub ? success : { r: 0.8, g: 0.6, b: 0 },
            },
        ];
        statBadge.appendChild(statTxt);

        badgeRow.appendChild(catBadge);
        badgeRow.appendChild(statBadge);
        body.appendChild(badgeRow);

        // Title
        const t = figma.createText();
        t.characters = titleText;
        t.fontName = { family: "Inter", style: "Bold" };
        t.fontSize = 18;
        t.fills = [{ type: "SOLID", color: textDark }];
        t.resize(304, t.height); // Auto height
        body.appendChild(t);

        // Excerpt
        const exc = figma.createText();
        exc.characters =
            "Leveraging data-driven insights to transform organizational governance and efficiency...";
        exc.fontSize = 13;
        exc.fills = [{ type: "SOLID", color: textMuted }];
        exc.resize(304, 36); // Max 2 lines height approx
        exc.textAutoResize = "HEIGHT"; // allow wrap
        body.appendChild(exc);

        // Meta Footer
        const footer = figma.createFrame();
        footer.layoutMode = "HORIZONTAL";
        footer.itemSpacing = 16;
        footer.paddingTop = 16;
        footer.resize(304, 40);
        footer.fills = [];
        // Garis pemisah atas footer
        // footer.strokes = [{type:'SOLID', color: border}]; footer.strokeTopWeight = 1; // Figma API bit complex for specific side stroke in basic frame, skip for now.

        // Date
        const dateWrap = figma.createFrame();
        dateWrap.layoutMode = "HORIZONTAL";
        dateWrap.itemSpacing = 6;
        dateWrap.counterAxisAlignItems = "CENTER";
        dateWrap.fills = [];
        const calIcon = figma.createNodeFromSvg(icons.calendar);
        calIcon.children[0].strokes = [{ type: "SOLID", color: textMuted }];
        const dTxt = figma.createText();
        dTxt.characters = dateStr;
        dTxt.fontSize = 12;
        dTxt.fills = [{ type: "SOLID", color: textMuted }];
        dateWrap.appendChild(calIcon);
        dateWrap.appendChild(dTxt);

        // Author
        const authWrap = figma.createFrame();
        authWrap.layoutMode = "HORIZONTAL";
        authWrap.itemSpacing = 6;
        authWrap.counterAxisAlignItems = "CENTER";
        authWrap.fills = [];
        const usrIcon = figma.createNodeFromSvg(icons.user);
        usrIcon.children[0].strokes = [{ type: "SOLID", color: textMuted }];
        const aTxt = figma.createText();
        aTxt.characters = "Admin";
        aTxt.fontSize = 12;
        aTxt.fills = [{ type: "SOLID", color: textMuted }];
        authWrap.appendChild(usrIcon);
        authWrap.appendChild(aTxt);

        footer.appendChild(dateWrap);
        footer.appendChild(authWrap);
        body.appendChild(footer);

        card.appendChild(body);
        return card;
    }

    // --- POPULATE GRID ---
    const posts = [
        [
            "Transforming Infrastructure with AI",
            "Technology",
            "Published",
            "Oct 24, 2024",
        ],
        ["Sustainable Energy Solutions", "Energy", "Published", "Oct 20, 2024"],
        ["Crisis Management Strategies", "Governance", "Draft", "Oct 18, 2024"],
        ["The Future of Water Security", "Water", "Published", "Sep 30, 2024"],
        ["Digital Innovation in 2025", "Tech", "Draft", "Sep 15, 2024"],
        ["Resilient Economic Growth", "Economy", "Published", "Sep 10, 2024"],
    ];

    posts.forEach((post) => {
        grid.appendChild(createBlogCard(post[0], post[1], post[2], post[3]));
    });

    content.appendChild(grid);
    pageFrame.appendChild(content);

    // Zoom to page
    figma.currentPage.appendChild(pageFrame);
    figma.viewport.scrollAndZoomIntoView([pageFrame]);
    figma.notify("Halaman Blog Management berhasil dibuat!");
})();

(async () => {
    try {
        figma.notify("Membuat tampilan List Blog Formal...");

        // 1. Load Fonts
        await figma.loadFontAsync({ family: "Inter", style: "Regular" });
        await figma.loadFontAsync({ family: "Inter", style: "Medium" });
        await figma.loadFontAsync({ family: "Inter", style: "Bold" });

        // --- CONFIG WARNA (Formal Palette) ---
        const primary = { r: 0, g: 0.188, b: 0.341 }; // Navy #003057
        const bgLight = { r: 0.97, g: 0.98, b: 0.99 };
        const white = { r: 1, g: 1, b: 1 };
        const textDark = { r: 0.1, g: 0.1, b: 0.12 };
        const textMuted = { r: 0.5, g: 0.5, b: 0.55 };
        const border = { r: 0.9, g: 0.92, b: 0.94 };

        // Status Colors (Subtle/Formal)
        const successBg = { r: 0.9, g: 1, b: 0.9 };
        const successTxt = { r: 0.1, g: 0.6, b: 0.3 };
        const draftBg = { r: 0.96, g: 0.96, b: 0.96 }; // Grey for draft
        const draftTxt = { r: 0.4, g: 0.4, b: 0.45 };

        // --- ICONS ---
        const icons = {
            search: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>`,
            plus: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>`,
            trash: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>`,
            dots: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>`,
            sort: `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 15l5 5 5-5"></path><path d="M7 9l5-5 5 5"></path></svg>`,
        };

        // --- 2. CARI FRAME TARGET ---
        const targetFrame = figma.currentPage.findOne(
            (n) => n.name === "Blog" && n.type === "FRAME"
        );

        if (!targetFrame) {
            figma.notify(
                "Frame 'Blog' tidak ditemukan! Pastikan nama frame sudah benar."
            );
            return;
        }

        // Bersihkan konten lama
        const oldContent = targetFrame.findOne(
            (n) => n.name === "Content Wrapper"
        );
        if (oldContent) oldContent.remove();

        // --- 3. BUILD STRUKTUR KONTEN ---
        const content = figma.createFrame();
        content.name = "Content Wrapper";
        content.layoutMode = "VERTICAL";
        content.resize(1080, 900);
        content.x = 280 + 40; // Sidebar (280) + Padding (40)
        content.y = 72 + 40; // Navbar (72) + Padding (40)
        content.itemSpacing = 24;
        content.fills = [];

        // --- A. HEADER (Judul & Tombol) ---
        const headerRow = figma.createFrame();
        headerRow.layoutMode = "HORIZONTAL";
        headerRow.primaryAxisAlignItems = "SPACE_BETWEEN";
        headerRow.counterAxisAlignItems = "CENTER";
        headerRow.resize(1080, 50);
        headerRow.fills = [];

        // Title
        const titleStack = figma.createFrame();
        titleStack.layoutMode = "VERTICAL";
        titleStack.itemSpacing = 4;
        titleStack.fills = [];
        const h1 = figma.createText();
        h1.characters = "Blog Posts";
        h1.fontName = { family: "Inter", style: "Bold" };
        h1.fontSize = 24;
        h1.fills = [{ type: "SOLID", color: textDark }];
        const sub = figma.createText();
        sub.characters = "Manage all your blog content here.";
        sub.fontSize = 14;
        sub.fills = [{ type: "SOLID", color: textMuted }];
        titleStack.appendChild(h1);
        titleStack.appendChild(sub);
        headerRow.appendChild(titleStack);

        // Actions (Search & Button)
        const actions = figma.createFrame();
        actions.layoutMode = "HORIZONTAL";
        actions.itemSpacing = 16;
        actions.fills = [];

        const searchBox = figma.createFrame();
        searchBox.layoutMode = "HORIZONTAL";
        searchBox.counterAxisAlignItems = "CENTER";
        searchBox.itemSpacing = 8;
        searchBox.paddingLeft = 12;
        searchBox.paddingRight = 12;
        searchBox.resize(240, 36);
        searchBox.cornerRadius = 6;
        searchBox.fills = [{ type: "SOLID", color: white }];
        searchBox.strokes = [{ type: "SOLID", color: border }];
        const searchIcon = figma.createNodeFromSvg(icons.search);
        searchIcon.children[0].strokes = [{ type: "SOLID", color: textMuted }];
        const searchPlace = figma.createText();
        searchPlace.characters = "Search...";
        searchPlace.fontSize = 13;
        searchPlace.fills = [{ type: "SOLID", color: textMuted }];
        searchBox.appendChild(searchIcon);
        searchBox.appendChild(searchPlace);
        actions.appendChild(searchBox);

        const createBtn = figma.createFrame();
        createBtn.layoutMode = "HORIZONTAL";
        createBtn.counterAxisAlignItems = "CENTER";
        createBtn.itemSpacing = 8;
        createBtn.paddingLeft = 16;
        createBtn.paddingRight = 16;
        createBtn.resize(100, 36);
        createBtn.cornerRadius = 6;
        createBtn.fills = [{ type: "SOLID", color: primary }];
        const plusIcon = figma.createNodeFromSvg(icons.plus);
        plusIcon.children[0].strokes = [{ type: "SOLID", color: white }];
        const btnTxt = figma.createText();
        btnTxt.characters = "Create Post";
        btnTxt.fontName = { family: "Inter", style: "Medium" };
        btnTxt.fontSize = 13;
        btnTxt.fills = [{ type: "SOLID", color: white }];
        createBtn.appendChild(plusIcon);
        createBtn.appendChild(btnTxt);
        actions.appendChild(createBtn);
        headerRow.appendChild(actions);
        content.appendChild(headerRow);

        // --- B. TABS ---
        const filters = figma.createFrame();
        filters.layoutMode = "HORIZONTAL";
        filters.itemSpacing = 32;
        filters.fills = [];
        const tabsData = [
            { label: "All Posts", count: "124", active: true },
            { label: "Published", count: "98", active: false },
            { label: "Drafts", count: "12", active: false },
            { label: "Trash", count: "10", active: false, icon: "trash" },
        ];

        tabsData.forEach((tab) => {
            const tContainer = figma.createFrame();
            tContainer.layoutMode = "VERTICAL";
            tContainer.itemSpacing = 8;
            tContainer.fills = [];

            const tInner = figma.createFrame();
            tInner.layoutMode = "HORIZONTAL";
            tInner.itemSpacing = 6;
            tInner.counterAxisAlignItems = "CENTER";
            tInner.fills = [];
            if (tab.icon) {
                const ic = figma.createNodeFromSvg(icons[tab.icon]);
                ic.children[0].strokes = [{ type: "SOLID", color: textMuted }];
                tInner.appendChild(ic);
            }
            const label = figma.createText();
            label.characters = tab.label;
            label.fontSize = 13;
            label.fontName = {
                family: "Inter",
                style: tab.active ? "Bold" : "Medium",
            };
            label.fills = [
                { type: "SOLID", color: tab.active ? primary : textMuted },
            ];
            tInner.appendChild(label);
            const count = figma.createFrame();
            count.paddingLeft = 6;
            count.paddingRight = 6;
            count.paddingTop = 2;
            count.paddingBottom = 2;
            count.cornerRadius = 100;
            count.fills = [
                {
                    type: "SOLID",
                    color: tab.active
                        ? { r: 0.9, g: 0.92, b: 0.95 }
                        : { r: 0.95, g: 0.95, b: 0.95 },
                },
            ];
            const countTxt = figma.createText();
            countTxt.characters = tab.count;
            countTxt.fontSize = 11;
            countTxt.fills = [
                { type: "SOLID", color: tab.active ? primary : textMuted },
            ];
            count.appendChild(countTxt);
            tInner.appendChild(count);
            tContainer.appendChild(tInner);

            if (tab.active) {
                const line = figma.createFrame();
                line.resize(10, 2);
                line.layoutAlign = "STRETCH";
                line.fills = [{ type: "SOLID", color: primary }];
                tContainer.appendChild(line);
            } else {
                const spacer = figma.createFrame();
                spacer.resize(10, 2);
                spacer.opacity = 0;
                tContainer.appendChild(spacer);
            }
            filters.appendChild(tContainer);
        });
        content.appendChild(filters);

        // --- C. TABLE CONTAINER ---
        const tableContainer = figma.createFrame();
        tableContainer.name = "Table List";
        tableContainer.layoutMode = "VERTICAL";
        tableContainer.resize(1080, 600);
        tableContainer.fills = [{ type: "SOLID", color: white }];
        tableContainer.strokes = [{ type: "SOLID", color: border }];
        tableContainer.cornerRadius = 8;
        tableContainer.clipsContent = true;

        // --- C.1 TABLE HEADER ---
        const tHead = figma.createFrame();
        tHead.name = "Table Header";
        tHead.layoutMode = "HORIZONTAL";
        tHead.resize(1080, 48); // Fixed height for header
        tHead.primaryAxisAlignItems = "SPACE_BETWEEN";
        tHead.counterAxisAlignItems = "CENTER";
        tHead.paddingLeft = 24;
        tHead.paddingRight = 24;
        tHead.fills = [{ type: "SOLID", color: { r: 0.98, g: 0.99, b: 1 } }]; // Very light background
        tHead.strokes = [{ type: "SOLID", color: border }];
        tHead.strokeBottomWeight = 1;
        tHead.strokeTopWeight = 0;
        tHead.strokeLeftWeight = 0;
        tHead.strokeRightWeight = 0;

        // Helper Column Header
        function createColHeader(text, width, grow = 0) {
            const col = figma.createFrame();
            col.layoutMode = "HORIZONTAL";
            col.itemSpacing = 4;
            col.counterAxisAlignItems = "CENTER";
            if (grow) col.layoutGrow = grow;
            else col.resize(width, 20);
            col.fills = [];

            const txt = figma.createText();
            txt.characters = text.toUpperCase();
            txt.fontSize = 11;
            txt.fontName = { family: "Inter", style: "Bold" };
            txt.letterSpacing = { value: 0.5, unit: "PIXELS" };
            txt.fills = [{ type: "SOLID", color: textMuted }];
            col.appendChild(txt);
            return col;
        }

        tHead.appendChild(createColHeader("Article Name", 0, 1)); // Grow
        tHead.appendChild(createColHeader("Category", 120));
        tHead.appendChild(createColHeader("Status", 100));
        tHead.appendChild(createColHeader("Date", 120));
        tHead.appendChild(createColHeader("Author", 100));
        tHead.appendChild(createColHeader("", 24)); // Action spacer
        tableContainer.appendChild(tHead);

        // --- C.2 TABLE ROWS (Generator) ---
        function createRow(title, category, status, date, author) {
            const row = figma.createFrame();
            row.name = "Row";
            row.layoutMode = "HORIZONTAL";
            row.resize(1080, 64); // Row height
            row.primaryAxisAlignItems = "SPACE_BETWEEN";
            row.counterAxisAlignItems = "CENTER";
            row.paddingLeft = 24;
            row.paddingRight = 24;
            row.fills = [{ type: "SOLID", color: white }];
            // Garis bawah tipis
            const line = figma.createFrame();
            line.resize(1080, 1);
            line.fills = [
                { type: "SOLID", color: { r: 0.96, g: 0.96, b: 0.96 } },
            ];
            // Figma trick: taruh garis di absolute position bottom
            // Tapi karena AutoLayout, kita pakai stroke bottom saja
            row.strokes = [
                { type: "SOLID", color: { r: 0.95, g: 0.95, b: 0.95 } },
            ];
            row.strokeBottomWeight = 1;
            row.strokeTopWeight = 0;
            row.strokeLeftWeight = 0;
            row.strokeRightWeight = 0;

            // 1. Article Name
            const col1 = figma.createFrame();
            col1.layoutMode = "VERTICAL";
            col1.itemSpacing = 4;
            col1.layoutGrow = 1;
            col1.fills = [];
            const t = figma.createText();
            t.characters = title;
            t.fontSize = 14;
            t.fontName = { family: "Inter", style: "Medium" };
            t.fills = [{ type: "SOLID", color: textDark }];
            col1.appendChild(t);
            row.appendChild(col1);

            // 2. Category
            const col2 = figma.createFrame();
            col2.layoutMode = "HORIZONTAL";
            col2.resize(120, 24);
            col2.fills = [];
            col2.counterAxisAlignItems = "CENTER";
            const badge = figma.createFrame();
            badge.cornerRadius = 4;
            badge.paddingLeft = 8;
            badge.paddingRight = 8;
            badge.paddingTop = 4;
            badge.paddingBottom = 4;
            badge.fills = [
                { type: "SOLID", color: { r: 0.94, g: 0.95, b: 0.97 } },
            ];
            const cTxt = figma.createText();
            cTxt.characters = category;
            cTxt.fontSize = 11;
            cTxt.fontName = { family: "Inter", style: "Medium" };
            cTxt.fills = [{ type: "SOLID", color: primary }];
            badge.appendChild(cTxt);
            col2.appendChild(badge);
            row.appendChild(col2);

            // 3. Status
            const col3 = figma.createFrame();
            col3.layoutMode = "HORIZONTAL";
            col3.resize(100, 24);
            col3.fills = [];
            col3.counterAxisAlignItems = "CENTER";
            const sBadge = figma.createFrame();
            sBadge.cornerRadius = 100;
            sBadge.paddingLeft = 8;
            sBadge.paddingRight = 8;
            sBadge.paddingTop = 2;
            sBadge.paddingBottom = 2;
            const isPub = status === "Published";
            sBadge.fills = [
                { type: "SOLID", color: isPub ? successBg : draftBg },
            ];
            const sTxt = figma.createText();
            sTxt.characters = status;
            sTxt.fontSize = 11;
            sTxt.fontName = { family: "Inter", style: "Medium" };
            sTxt.fills = [
                { type: "SOLID", color: isPub ? successTxt : draftTxt },
            ];
            sBadge.appendChild(sTxt);
            col3.appendChild(sBadge);
            row.appendChild(col3);

            // 4. Date
            const col4 = figma.createFrame();
            col4.layoutMode = "HORIZONTAL";
            col4.resize(120, 24);
            col4.fills = [];
            col4.counterAxisAlignItems = "CENTER";
            const dTxt = figma.createText();
            dTxt.characters = date;
            dTxt.fontSize = 13;
            dTxt.fills = [{ type: "SOLID", color: textMuted }];
            col4.appendChild(dTxt);
            row.appendChild(col4);

            // 5. Author
            const col5 = figma.createFrame();
            col5.layoutMode = "HORIZONTAL";
            col5.itemSpacing = 8;
            col5.resize(100, 24);
            col5.fills = [];
            col5.counterAxisAlignItems = "CENTER";
            const av = figma.createFrame();
            av.resize(24, 24);
            av.cornerRadius = 12;
            av.fills = [{ type: "SOLID", color: { r: 0.9, g: 0.9, b: 0.9 } }];
            const aTxt = figma.createText();
            aTxt.characters = author;
            aTxt.fontSize = 13;
            aTxt.fills = [{ type: "SOLID", color: textDark }];
            col5.appendChild(av);
            col5.appendChild(aTxt);
            row.appendChild(col5);

            // 6. Action
            const col6 = figma.createFrame();
            col6.layoutMode = "CENTER";
            col6.resize(24, 24);
            col6.fills = [];
            const dot = figma.createNodeFromSvg(icons.dots);
            dot.children[0].strokes = [{ type: "SOLID", color: textMuted }];
            col6.appendChild(dot);
            row.appendChild(col6);

            return row;
        }

        // --- POPULATE DATA ---
        const data = [
            {
                t: "Transforming Infrastructure with AI",
                c: "Technology",
                s: "Published",
                d: "Oct 24, 2024",
                a: "Admin",
            },
            {
                t: "Sustainable Energy Solutions 2025",
                c: "Energy",
                s: "Published",
                d: "Oct 22, 2024",
                a: "Sarah",
            },
            {
                t: "Global Crisis Management Strategies",
                c: "Governance",
                s: "Draft",
                d: "Oct 20, 2024",
                a: "Admin",
            },
            {
                t: "The Future of Water Security",
                c: "Water",
                s: "Published",
                d: "Oct 18, 2024",
                a: "John",
            },
            {
                t: "Digital Innovation Roadmap",
                c: "Tech",
                s: "Draft",
                d: "Oct 15, 2024",
                a: "Admin",
            },
            {
                t: "Economic Resilience in Asia",
                c: "Economy",
                s: "Published",
                d: "Oct 12, 2024",
                a: "Mike",
            },
            {
                t: "Green Building Standards",
                c: "Real Estate",
                s: "Published",
                d: "Oct 10, 2024",
                a: "Sarah",
            },
            {
                t: "Cybersecurity Protocol Updates",
                c: "Security",
                s: "Draft",
                d: "Oct 08, 2024",
                a: "John",
            },
        ];

        data.forEach((item) => {
            tableContainer.appendChild(
                createRow(item.t, item.c, item.s, item.d, item.a)
            );
        });

        content.appendChild(tableContainer);
        targetFrame.appendChild(content);

        figma.viewport.scrollAndZoomIntoView([targetFrame]);
        figma.notify("Berhasil! Tampilan Blog diubah menjadi List Formal.");
    } catch (error) {
        figma.notify("Error: " + error.message);
        console.error(error);
    }
})();

(async () => {
    await figma.loadFontAsync({ family: "Inter", style: "Regular" });
    await figma.loadFontAsync({ family: "Inter", style: "Medium" });
    await figma.loadFontAsync({ family: "Inter", style: "Bold" });

    const targetFrame = figma.currentPage.findOne(
        (n) => n.name.toLowerCase() === "blog" && n.type === "FRAME"
    );

    if (!targetFrame) {
        figma.notify("Frame 'blog' tidak ditemukan!");
        return;
    }

    // --- CONFIG WARNA ---
    const primary = { r: 0, g: 0.188, b: 0.341 }; // Cadmus Navy
    const bgLight = { r: 0.97, g: 0.98, b: 0.99 };
    const textDark = { r: 0.12, g: 0.13, b: 0.15 };
    const textMuted = { r: 0.45, g: 0.5, b: 0.55 };
    const white = { r: 1, g: 1, b: 1 };
    const borderColor = { r: 0.9, g: 0.92, b: 0.94 };

    // --- MAIN CONTENT WRAPPER ---
    const contentArea = figma.createFrame();
    contentArea.name = "CMS Dashboard Content";
    contentArea.layoutMode = "VERTICAL";
    contentArea.resize(targetFrame.width - 280, targetFrame.height - 72);
    contentArea.x = 280;
    contentArea.y = 72;
    contentArea.fills = [{ type: "SOLID", color: bgLight }];
    contentArea.paddingLeft = 40;
    contentArea.paddingRight = 40;
    contentArea.paddingTop = 40;
    contentArea.paddingBottom = 40;
    contentArea.itemSpacing = 24; // Dipersempit sedikit agar lebih kompak

    // --- SECTION 1: HEADER ---
    const header = figma.createFrame();
    header.layoutMode = "HORIZONTAL";
    header.primaryAxisAlignItems = "SPACE_BETWEEN";
    header.counterAxisAlignItems = "CENTER";
    header.primaryAxisSizingMode = "FIXED";
    header.resize(contentArea.width - 80, 60);
    header.fills = [];

    const titleStack = figma.createFrame();
    titleStack.layoutMode = "VERTICAL";
    titleStack.itemSpacing = 4;
    titleStack.fills = [];

    const title = figma.createText();
    title.characters = "Dashboard Overview";
    title.fontName = { family: "Inter", style: "Bold" };
    title.fontSize = 24;
    title.fills = [{ type: "SOLID", color: textDark }];

    const subTitle = figma.createText();
    subTitle.characters = "Monitoring summary of your CMS activities";
    subTitle.fontSize = 14;
    subTitle.fills = [{ type: "SOLID", color: textMuted }];

    titleStack.appendChild(title);
    titleStack.appendChild(subTitle);
    header.appendChild(titleStack);

    const btn = figma.createFrame();
    btn.layoutMode = "HORIZONTAL";
    btn.paddingLeft = 20;
    btn.paddingRight = 20;
    btn.paddingTop = 12;
    btn.paddingBottom = 12;
    btn.cornerRadius = 8;
    btn.fills = [{ type: "SOLID", color: primary }];
    const btnText = figma.createText();
    btnText.characters = "+ Create Post";
    btnText.fontName = { family: "Inter", style: "Medium" };
    btnText.fontSize = 14;
    btnText.fills = [{ type: "SOLID", color: white }];
    btn.appendChild(btnText);
    header.appendChild(btn);

    contentArea.appendChild(header);

    // --- SECTION 2: STAT CARDS ---
    const cardContainer = figma.createFrame();
    cardContainer.layoutMode = "HORIZONTAL";
    cardContainer.itemSpacing = 20;
    cardContainer.fills = [];

    function createStatCard(label, value, info) {
        const card = figma.createFrame();
        card.layoutMode = "VERTICAL";
        card.resize(265, 120);
        card.paddingLeft = 20;
        card.paddingTop = 20;
        card.cornerRadius = 12;
        card.fills = [{ type: "SOLID", color: white }];
        card.strokes = [{ type: "SOLID", color: borderColor }];

        const lbl = figma.createText();
        lbl.characters = label;
        lbl.fontSize = 12;
        lbl.fills = [{ type: "SOLID", color: textMuted }];
        const val = figma.createText();
        val.characters = value;
        val.fontName = { family: "Inter", style: "Bold" };
        val.fontSize = 26;
        val.fills = [{ type: "SOLID", color: textDark }];
        const inf = figma.createText();
        inf.characters = info;
        inf.fontSize = 11;
        inf.fills = [{ type: "SOLID", color: { r: 0.1, g: 0.6, b: 0.3 } }];

        card.appendChild(lbl);
        card.appendChild(val);
        card.appendChild(inf);
        return card;
    }

    cardContainer.appendChild(
        createStatCard("Total Posts", "1,248", "↑ 12 this week")
    );
    cardContainer.appendChild(createStatCard("Published", "1,180", "95% Live"));
    cardContainer.appendChild(createStatCard("Drafts", "68", "Needs review"));
    contentArea.appendChild(cardContainer);

    // --- SECTION 3: TABLE WITH FILTER TABS ---
    const tableSection = figma.createFrame();
    tableSection.name = "Recent Content Section";
    tableSection.layoutMode = "VERTICAL";
    tableSection.resize(contentArea.width - 80, 420);
    tableSection.paddingTop = 24;
    tableSection.cornerRadius = 16;
    tableSection.fills = [{ type: "SOLID", color: white }];
    tableSection.strokes = [{ type: "SOLID", color: borderColor }];
    tableSection.itemSpacing = 16;

    // Filter Tabs Container
    const tabsContainer = figma.createFrame();
    tabsContainer.layoutMode = "HORIZONTAL";
    tabsContainer.itemSpacing = 24;
    tabsContainer.paddingLeft = 24;
    tabsContainer.fills = [];

    function createTab(label, isActive = false) {
        const tab = figma.createFrame();
        tab.layoutMode = "VERTICAL";
        tab.itemSpacing = 8;
        tab.fills = [];

        const tText = figma.createText();
        tText.characters = label;
        tText.fontName = {
            family: "Inter",
            style: isActive ? "Bold" : "Medium",
        };
        tText.fontSize = 14;
        tText.fills = [
            { type: "SOLID", color: isActive ? primary : textMuted },
        ];

        tab.appendChild(tText);

        if (isActive) {
            const underline = figma.createFrame();
            underline.resize(tText.width, 2);
            underline.fills = [{ type: "SOLID", color: primary }];
            tab.appendChild(underline);
        }
        return tab;
    }

    tabsContainer.appendChild(createTab("All Content", true));
    tabsContainer.appendChild(createTab("Published"));
    tabsContainer.appendChild(createTab("Drafts"));
    tabsContainer.appendChild(createTab("Scheduled"));
    tableSection.appendChild(tabsContainer);

    // Header Row Table
    const tableHeader = figma.createFrame();
    tableHeader.layoutMode = "HORIZONTAL";
    tableHeader.resize(tableSection.width, 44);
    tableHeader.paddingLeft = 24;
    tableHeader.counterAxisAlignItems = "CENTER";
    tableHeader.fills = [{ type: "SOLID", color: bgLight }];

    ["ARTICLE TITLE", "CATEGORY", "STATUS", "LAST UPDATED"].forEach((text) => {
        const hText = figma.createText();
        hText.characters = text;
        hText.fontSize = 11;
        hText.fontName = { family: "Inter", style: "Bold" };
        hText.fills = [{ type: "SOLID", color: textMuted }];
        hText.resize(160, hText.height);
        tableHeader.appendChild(hText);
    });
    tableSection.appendChild(tableHeader);

    // Mockup Data Rows
    const data = [
        ["Modern CMS Trends", "Tech", "Published", "2 hours ago"],
        ["Optimizing SEO 2024", "Marketing", "Draft", "5 hours ago"],
        ["Design Systems in UI", "Design", "Published", "1 day ago"],
    ];

    data.forEach((rowItem) => {
        const row = figma.createFrame();
        row.layoutMode = "HORIZONTAL";
        row.resize(tableSection.width, 52);
        row.paddingLeft = 24;
        row.counterAxisAlignItems = "CENTER";
        rowItem.forEach((cell) => {
            const cText = figma.createText();
            cText.characters = cell;
            cText.fontSize = 13;
            cText.fills = [{ type: "SOLID", color: textDark }];
            cText.resize(160, cText.height);
            row.appendChild(cText);
        });
        tableSection.appendChild(row);
    });

    contentArea.appendChild(tableSection);

    // --- ASSEMBLY ---
    targetFrame.appendChild(contentArea);
    figma.viewport.scrollAndZoomIntoView([contentArea]);
    figma.notify("Dashboard CMS dengan Filter Tabs berhasil dibuat!");
})();


(async () => {
    // Fonts
    await figma.loadFontAsync({ family: "Inter", style: "Regular" });
    await figma.loadFontAsync({ family: "Inter", style: "Medium" });
    await figma.loadFontAsync({ family: "Inter", style: "Bold" });

    // ===== Theme (Cadmus-ish) =====
    const primary = { r: 0, g: 0.188, b: 0.341 }; // #003057
    const bgLight = { r: 0.97, g: 0.98, b: 0.99 };
    const white = { r: 1, g: 1, b: 1 };
    const textDark = { r: 0.12, g: 0.13, b: 0.15 };
    const textMuted = { r: 0.45, g: 0.5, b: 0.55 };
    const border = { r: 0.9, g: 0.92, b: 0.94 };
    const danger = { r: 0.8, g: 0.12, b: 0.12 };
    const success = { r: 0.12, g: 0.63, b: 0.36 };

    const solid = (color) => [{ type: "SOLID", color }];

    function txt({ value, size = 12, style = "Regular", color = textDark }) {
        const t = figma.createText();
        t.fontName = { family: "Inter", style };
        t.fontSize = size;
        t.fills = solid(color);
        t.characters = value;
        return t;
    }

    // ===== Layout constants (Stisla-ish dashboard grid) =====
    const PAGE_W = 1440;
    const PAGE_H = 980;
    const PAGE_PAD = 40;

    const GRID_GAP = 20;
    const COLS = 3;

    // Ini kuncinya: card width dihitung dari page, bukan angka random
    // Total usable width = PAGE_W - (PAGE_PAD*2) - (GRID_GAP*(COLS-1))
    const USABLE_W = PAGE_W - PAGE_PAD * 2 - GRID_GAP * (COLS - 1);
    const CARD_W = Math.floor(USABLE_W / COLS); // lebar card per kolom
    const CARD_PAD = 16;
    const FIELD_H = 44;

    // ===== Components =====
    function cardBase(name) {
        const c = figma.createFrame();
        c.name = name;

        c.layoutMode = "VERTICAL";
        c.primaryAxisSizingMode = "AUTO";
        c.counterAxisSizingMode = "FIXED";
        c.resize(CARD_W, 10);

        c.paddingLeft = CARD_PAD;
        c.paddingRight = CARD_PAD;
        c.paddingTop = CARD_PAD;
        c.paddingBottom = CARD_PAD;
        c.itemSpacing = 10;

        c.cornerRadius = 14;
        c.fills = solid(white);
        c.strokes = [{ type: "SOLID", color: border }];
        c.strokeAlign = "INSIDE";
        c.clipsContent = false;

        c.effects = [
            {
                type: "DROP_SHADOW",
                color: { r: 0, g: 0, b: 0, a: 0.05 },
                offset: { x: 0, y: 4 },
                radius: 12,
                visible: true,
                blendMode: "NORMAL",
            },
        ];

        return c;
    }

    function labelRow(label, required = false, helperRight = "") {
        const row = figma.createFrame();
        row.layoutMode = "HORIZONTAL";
        row.primaryAxisAlignItems = "SPACE_BETWEEN";
        row.counterAxisAlignItems = "CENTER";
        row.layoutAlign = "STRETCH";
        row.fills = [];

        const left = figma.createFrame();
        left.layoutMode = "HORIZONTAL";
        left.itemSpacing = 4;
        left.fills = [];

        left.appendChild(
            txt({ value: label, size: 12, style: "Medium", color: textDark })
        );
        if (required)
            left.appendChild(
                txt({ value: "*", size: 12, style: "Medium", color: danger })
            );

        row.appendChild(left);

        if (helperRight) {
            row.appendChild(
                txt({
                    value: helperRight,
                    size: 11,
                    style: "Regular",
                    color: textMuted,
                })
            );
        }

        return row;
    }

    function helperText(value, type = "muted") {
        const c =
            type === "danger"
                ? danger
                : type === "success"
                ? success
                : textMuted;
        const t = txt({ value, size: 11, style: "Regular", color: c });
        t.layoutAlign = "STRETCH";
        return t;
    }

    function fieldBox({
        placeholder = "Type here...",
        height = FIELD_H,
        rightAffix = "",
    }) {
        const f = figma.createFrame();
        f.layoutMode = "HORIZONTAL";
        f.primaryAxisAlignItems = "SPACE_BETWEEN";
        f.counterAxisAlignItems = "CENTER";

        // stretch = ikut lebar card
        f.layoutAlign = "STRETCH";
        f.resize(CARD_W - CARD_PAD * 2, height);

        f.paddingLeft = 12;
        f.paddingRight = 12;
        f.cornerRadius = 10;
        f.fills = solid(white);
        f.strokes = [{ type: "SOLID", color: border }];
        f.strokeAlign = "INSIDE";

        const ph = txt({
            value: placeholder,
            size: 13,
            style: "Regular",
            color: textMuted,
        });
        f.appendChild(ph);

        if (rightAffix) {
            f.appendChild(
                txt({
                    value: rightAffix,
                    size: 12,
                    style: "Medium",
                    color: textMuted,
                })
            );
        }

        return f;
    }

    function makeTextarea(placeholder = "Write something...") {
        const f = figma.createFrame();
        f.layoutMode = "HORIZONTAL";
        f.layoutAlign = "STRETCH";
        f.resize(CARD_W - CARD_PAD * 2, 120);

        f.paddingLeft = 12;
        f.paddingRight = 12;
        f.paddingTop = 12;
        f.paddingBottom = 12;
        f.cornerRadius = 10;
        f.fills = solid(white);
        f.strokes = [{ type: "SOLID", color: border }];
        f.strokeAlign = "INSIDE";

        f.appendChild(
            txt({
                value: placeholder,
                size: 13,
                style: "Regular",
                color: textMuted,
            })
        );
        return f;
    }

    function makeSelect(placeholder = "Select option") {
        return fieldBox({ placeholder, rightAffix: "▾" });
    }

    function makeDate(placeholder = "DD/MM/YYYY") {
        const f = figma.createFrame();
        f.layoutMode = "HORIZONTAL";
        f.primaryAxisAlignItems = "SPACE_BETWEEN";
        f.counterAxisAlignItems = "CENTER";
        f.layoutAlign = "STRETCH";
        f.resize(CARD_W - CARD_PAD * 2, FIELD_H);

        f.paddingLeft = 12;
        f.paddingRight = 12;
        f.cornerRadius = 10;
        f.fills = solid(white);
        f.strokes = [{ type: "SOLID", color: border }];
        f.strokeAlign = "INSIDE";

        const left = figma.createFrame();
        left.layoutMode = "HORIZONTAL";
        left.counterAxisAlignItems = "CENTER";
        left.itemSpacing = 8;
        left.fills = [];

        left.appendChild(
            txt({ value: "📅", size: 14, style: "Medium", color: textMuted })
        );
        left.appendChild(
            txt({
                value: placeholder,
                size: 13,
                style: "Regular",
                color: textMuted,
            })
        );

        f.appendChild(left);
        f.appendChild(
            txt({ value: "▾", size: 12, style: "Medium", color: textMuted })
        );
        return f;
    }

    function makeCheckboxRow(label, checked = false) {
        const row = figma.createFrame();
        row.layoutMode = "HORIZONTAL";
        row.counterAxisAlignItems = "CENTER";
        row.itemSpacing = 10;
        row.layoutAlign = "STRETCH";
        row.fills = [];

        const box = figma.createFrame();
        box.resize(18, 18);
        box.cornerRadius = 4;

        if (checked) {
            box.fills = solid(primary);
            box.strokes = [];
            const tick = txt({
                value: "✓",
                size: 12,
                style: "Bold",
                color: white,
            });
            tick.x = 4;
            tick.y = 1;
            box.appendChild(tick);
        } else {
            box.fills = solid(white);
            box.strokes = [{ type: "SOLID", color: border }];
        }

        row.appendChild(box);
        row.appendChild(
            txt({ value: label, size: 13, style: "Regular", color: textDark })
        );
        return row;
    }

    function makeRadioRow(label, selected = false) {
        const row = figma.createFrame();
        row.layoutMode = "HORIZONTAL";
        row.counterAxisAlignItems = "CENTER";
        row.itemSpacing = 10;
        row.layoutAlign = "STRETCH";
        row.fills = [];

        const outer = figma.createFrame();
        outer.resize(18, 18);
        outer.cornerRadius = 999;
        outer.fills = solid(white);
        outer.strokes = [{ type: "SOLID", color: selected ? primary : border }];

        if (selected) {
            const dot = figma.createFrame();
            dot.resize(10, 10);
            dot.cornerRadius = 999;
            dot.fills = solid(primary);
            dot.x = 4;
            dot.y = 4;
            outer.appendChild(dot);
        }

        row.appendChild(outer);
        row.appendChild(
            txt({ value: label, size: 13, style: "Regular", color: textDark })
        );
        return row;
    }

    function makeToggle(on = true, label = "Enabled") {
        const wrap = figma.createFrame();
        wrap.layoutMode = "HORIZONTAL";
        wrap.counterAxisAlignItems = "CENTER";
        wrap.primaryAxisAlignItems = "SPACE_BETWEEN";
        wrap.layoutAlign = "STRETCH";
        wrap.fills = [];

        wrap.appendChild(
            txt({ value: label, size: 13, style: "Regular", color: textDark })
        );

        const bg = figma.createFrame();
        bg.resize(44, 24);
        bg.cornerRadius = 999;
        bg.fills = solid(on ? primary : { r: 0.85, g: 0.87, b: 0.9 });

        const knob = figma.createFrame();
        knob.resize(20, 20);
        knob.cornerRadius = 999;
        knob.fills = solid(white);
        knob.x = on ? 22 : 2;
        knob.y = 2;

        bg.appendChild(knob);
        wrap.appendChild(bg);

        return wrap;
    }

    function makeUpload() {
        const u = figma.createFrame();
        u.layoutMode = "VERTICAL";
        u.primaryAxisAlignItems = "CENTER";
        u.counterAxisAlignItems = "CENTER";
        u.itemSpacing = 8;

        u.layoutAlign = "STRETCH";
        u.resize(CARD_W - CARD_PAD * 2, 120);

        u.cornerRadius = 12;
        u.fills = solid({ r: 0.98, g: 0.99, b: 1 });
        u.strokes = [{ type: "SOLID", color: primary }];
        u.strokeDashPattern = [4, 4];
        u.strokeAlign = "INSIDE";

        u.appendChild(
            txt({ value: "⤒", size: 16, style: "Medium", color: primary })
        );
        u.appendChild(
            txt({
                value: "Click to upload",
                size: 13,
                style: "Medium",
                color: primary,
            })
        );
        u.appendChild(
            txt({
                value: "PNG/JPG/SVG up to 5MB",
                size: 11,
                style: "Regular",
                color: textMuted,
            })
        );
        return u;
    }

    function makeButtonsRow() {
        const row = figma.createFrame();
        row.layoutMode = "HORIZONTAL";
        row.itemSpacing = 12;
        row.layoutAlign = "STRETCH";
        row.fills = [];

        const btn1 = figma.createFrame();
        btn1.layoutMode = "HORIZONTAL";
        btn1.primaryAxisAlignItems = "CENTER";
        btn1.counterAxisAlignItems = "CENTER";
        btn1.paddingLeft = 18;
        btn1.paddingRight = 18;
        btn1.paddingTop = 10;
        btn1.paddingBottom = 10;
        btn1.cornerRadius = 10;
        btn1.fills = solid(primary);
        btn1.resize(160, 44);
        btn1.appendChild(
            txt({ value: "Primary", size: 13, style: "Medium", color: white })
        );

        const btn2 = figma.createFrame();
        btn2.layoutMode = "HORIZONTAL";
        btn2.primaryAxisAlignItems = "CENTER";
        btn2.counterAxisAlignItems = "CENTER";
        btn2.paddingLeft = 18;
        btn2.paddingRight = 18;
        btn2.paddingTop = 10;
        btn2.paddingBottom = 10;
        btn2.cornerRadius = 10;
        btn2.fills = solid(white);
        btn2.strokes = [{ type: "SOLID", color: border }];
        btn2.resize(140, 44);
        btn2.appendChild(
            txt({
                value: "Secondary",
                size: 13,
                style: "Medium",
                color: textDark,
            })
        );

        row.appendChild(btn1);
        row.appendChild(btn2);
        return row;
    }

    function column(name) {
        const col = figma.createFrame();
        col.name = name;
        col.layoutMode = "VERTICAL";
        col.primaryAxisSizingMode = "AUTO";
        col.counterAxisSizingMode = "FIXED";
        col.resize(CARD_W, 10);
        col.itemSpacing = 20;
        col.fills = [];
        return col;
    }

    // ===== Create page/frame =====
    const page = figma.createFrame();
    page.name = "Input Templates Library (Stisla-like)";
    page.resize(PAGE_W, PAGE_H);
    page.fills = solid(bgLight);

    // place next to existing frames
    let startX = 0;
    figma.currentPage.children.forEach((n) => {
        if (n.type === "FRAME") startX = Math.max(startX, n.x + n.width);
    });
    page.x = startX + 120;
    page.y = 0;

    // Header
    const header = figma.createFrame();
    header.name = "Header";
    header.layoutMode = "HORIZONTAL";
    header.primaryAxisAlignItems = "SPACE_BETWEEN";
    header.counterAxisAlignItems = "CENTER";
    header.fills = [];
    header.resize(PAGE_W - PAGE_PAD * 2, 80);
    header.x = PAGE_PAD;
    header.y = PAGE_PAD;

    const titleStack = figma.createFrame();
    titleStack.layoutMode = "VERTICAL";
    titleStack.itemSpacing = 4;
    titleStack.fills = [];
    titleStack.appendChild(
        txt({
            value: "Input Templates",
            size: 26,
            style: "Bold",
            color: textDark,
        })
    );
    titleStack.appendChild(
        txt({
            value: "Copas frame yang kamu butuhkan. Biar konsisten dan tidak bikin ulang terus.",
            size: 13,
            style: "Regular",
            color: textMuted,
        })
    );

    const badge = figma.createFrame();
    badge.layoutMode = "HORIZONTAL";
    badge.primaryAxisAlignItems = "CENTER";
    badge.counterAxisAlignItems = "CENTER";
    badge.paddingLeft = 12;
    badge.paddingRight = 12;
    badge.paddingTop = 8;
    badge.paddingBottom = 8;
    badge.cornerRadius = 999;
    badge.fills = solid({ r: 0.92, g: 0.95, b: 0.99 });
    badge.appendChild(
        txt({
            value: "Cadmus UI Kit",
            size: 12,
            style: "Medium",
            color: primary,
        })
    );

    header.appendChild(titleStack);
    header.appendChild(badge);
    page.appendChild(header);

    // Grid container
    const grid = figma.createFrame();
    grid.name = "Templates Grid";
    grid.layoutMode = "HORIZONTAL";
    grid.itemSpacing = GRID_GAP;
    grid.fills = [];
    grid.resize(PAGE_W - PAGE_PAD * 2, 10);
    grid.x = PAGE_PAD;
    grid.y = PAGE_PAD + 100;

    const col1 = column("Column 1");
    const col2 = column("Column 2");
    const col3 = column("Column 3");

    // ===== Cards / Templates =====
    // Text
    const cText = cardBase("Template: Text Input");
    cText.appendChild(labelRow("Full Name", true));
    cText.appendChild(fieldBox({ placeholder: "e.g. Jane Doe" }));
    cText.appendChild(helperText("This will be shown on your profile."));
    col1.appendChild(cText);

    // Email
    const cEmail = cardBase("Template: Email Input");
    cEmail.appendChild(labelRow("Email", true));
    cEmail.appendChild(fieldBox({ placeholder: "name@company.com" }));
    cEmail.appendChild(helperText("We will never share your email."));
    col1.appendChild(cEmail);

    // Password
    const cPass = cardBase("Template: Password Input");
    cPass.appendChild(labelRow("Password", true, "Min 8 chars"));
    cPass.appendChild(fieldBox({ placeholder: "••••••••", rightAffix: "👁" }));
    cPass.appendChild(helperText("Use a strong password."));
    col1.appendChild(cPass);

    // Radio group
    const cRadio = cardBase("Template: Radio Group");
    cRadio.appendChild(labelRow("Plan", true));
    cRadio.appendChild(makeRadioRow("Free", true));
    cRadio.appendChild(makeRadioRow("Pro", false));
    cRadio.appendChild(makeRadioRow("Enterprise", false));
    cRadio.appendChild(helperText("Pick exactly one."));
    col1.appendChild(cRadio);

    // Buttons
    const cBtns = cardBase("Template: Buttons");
    cBtns.appendChild(labelRow("Actions"));
    cBtns.appendChild(makeButtonsRow());
    cBtns.appendChild(helperText("Primary + secondary CTA."));
    col1.appendChild(cBtns);

    // Number
    const cNum = cardBase("Template: Number Input");
    cNum.appendChild(labelRow("Budget", false, "IDR"));
    cNum.appendChild(fieldBox({ placeholder: "0", rightAffix: "IDR" }));
    cNum.appendChild(helperText("Numbers only."));
    col2.appendChild(cNum);

    // Search
    const cSearch = cardBase("Template: Search Input");
    cSearch.appendChild(labelRow("Search"));
    cSearch.appendChild(
        fieldBox({ placeholder: "Search...", rightAffix: "⌕" })
    );
    cSearch.appendChild(helperText("Type to filter results."));
    col2.appendChild(cSearch);

    // Textarea
    const cArea = cardBase("Template: Textarea");
    cArea.appendChild(labelRow("Description", false, "Max 500"));
    cArea.appendChild(makeTextarea("Write your description..."));
    cArea.appendChild(helperText("Keep it short and readable."));
    col2.appendChild(cArea);

    // Toggle
    const cToggle = cardBase("Template: Toggle");
    cToggle.appendChild(labelRow("Feature Flag"));
    cToggle.appendChild(makeToggle(true, "Enabled"));
    cToggle.appendChild(helperText("Toggle to enable/disable."));
    col2.appendChild(cToggle);

    // Validation states
    const cState = cardBase("Template: Validation States");
    cState.appendChild(labelRow("Username", true));
    const okField = fieldBox({ placeholder: "available_username" });
    okField.strokes = [{ type: "SOLID", color: success }];
    cState.appendChild(okField);
    cState.appendChild(helperText("Looks good.", "success"));

    cState.appendChild(labelRow("Username", true));
    const badField = fieldBox({ placeholder: "taken_username" });
    badField.strokes = [{ type: "SOLID", color: danger }];
    cState.appendChild(badField);
    cState.appendChild(helperText("This username is already taken.", "danger"));
    col2.appendChild(cState);

    // Select
    const cSelect = cardBase("Template: Select");
    cSelect.appendChild(labelRow("Category"));
    cSelect.appendChild(makeSelect("Select category"));
    cSelect.appendChild(helperText("Example: Marketing, Design, Tech."));
    col3.appendChild(cSelect);

    // Date
    const cDate = cardBase("Template: Date Picker");
    cDate.appendChild(labelRow("Due Date"));
    cDate.appendChild(makeDate("DD/MM/YYYY"));
    cDate.appendChild(helperText("Use consistent date format."));
    col3.appendChild(cDate);

    // Checkbox
    const cCheck = cardBase("Template: Checkbox Group");
    cCheck.appendChild(labelRow("Preferences"));
    cCheck.appendChild(makeCheckboxRow("Enable notifications", true));
    cCheck.appendChild(makeCheckboxRow("Send weekly digest", false));
    cCheck.appendChild(makeCheckboxRow("Make profile public", false));
    col3.appendChild(cCheck);

    // Upload
    const cUpload = cardBase("Template: File Upload");
    cUpload.appendChild(labelRow("Attachments"));
    cUpload.appendChild(makeUpload());
    cUpload.appendChild(helperText("Drag and drop supported."));
    col3.appendChild(cUpload);

    // Assemble grid
    grid.appendChild(col1);
    grid.appendChild(col2);
    grid.appendChild(col3);

    page.appendChild(grid);
    figma.currentPage.appendChild(page);

    figma.viewport.scrollAndZoomIntoView([page]);
    figma.notify(
        "Done: Input Templates Library (layout rapi, ukuran konsisten)."
    );
})();


