# Landing Page PRP Template v2 - SEO Optimized & Visually Stunning

## Purpose

Template optimized for creating high-converting landing pages with exceptional visual design, optimal SEO performance, and modern user experience through iterative refinement.

## Core Principles

1. **Performance First**: Sub-3 second load times, Core Web Vitals optimization
2. **SEO Foundation**: Semantic HTML, structured data, meta optimization
3. **Visual Impact**: Modern design patterns that convert visitors
4. **Mobile-First**: Responsive design with progressive enhancement
5. **Conversion Focus**: Clear CTAs and user journey optimization

---

## Goal

[Specific landing page purpose - product launch, lead generation, brand showcase, etc.]

## Why

- [Business objective and conversion goals]
- [Target audience and their pain points]
- [Competitive advantage to communicate]
- [Revenue/lead generation targets]

## What

[User experience and visual requirements]

### Success Criteria

- [ ] Page Speed Score >90 (Desktop) >80 (Mobile)
- [ ] Core Web Vitals: LCP <2.5s, FID <100ms, CLS <0.1
- [ ] SEO Score >95 (Lighthouse)
- [ ] Conversion rate target: [X%]
- [ ] Mobile responsiveness across all devices

## All Needed Context

### Design & SEO References

```yaml
# MUST REVIEW - Include these in your research
- inspiration: [Competitor/inspiration URL]
  why: [Specific design patterns or conversion elements]

- seo_analysis: [Top ranking competitor URL]
  why: [Keyword strategy, meta patterns, structured data]

- design_system: [Brand guidelines URL/file]
  why: [Colors, fonts, spacing, component library]

- wireframes: [Figma/design file URL]
  why: [Layout structure, component hierarchy]

- copy_doc: [Content strategy document]
  why: [Headlines, value props, CTA copy]
```

### Target Audience & Keywords

```yaml
primary_keywords:
  - "[main keyword]" (volume: X, difficulty: Y)
  - "[secondary keyword]" (volume: X, difficulty: Y)

long_tail_keywords:
  - "[specific phrase]"
  - "[problem-solution phrase]"

user_intent:
  - primary: [informational/transactional/navigational]
  - pain_points: [specific user problems]
  - desired_outcomes: [what success looks like for user]
```

### Technical Stack & Assets

```yaml
framework: [Next.js/Nuxt/Astro/Static]
styling: [Tailwind/Styled-components/SCSS]
animations: [Framer Motion/GSAP/CSS animations]
images: [source folder/CDN/asset requirements]
fonts: [Google Fonts/custom fonts with loading strategy]
analytics: [Google Analytics/Mixpanel/custom tracking]
```

## Implementation Blueprint

### SEO Foundation

```html
<!-- Critical SEO elements -->
<head>
  <!-- Primary Meta Tags -->
  <title>[60 chars max, keyword-rich, compelling]</title>
  <meta
    name="description"
    content="[155 chars, includes primary keyword, actionable]"
  />
  <meta name="keywords" content="[primary, secondary, long-tail keywords]" />

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="website" />
  <meta property="og:title" content="[compelling social title]" />
  <meta property="og:description" content="[social-optimized description]" />
  <meta property="og:image" content="[1200x630px social image URL]" />

  <!-- Twitter -->
  <meta property="twitter:card" content="summary_large_image" />
  <meta property="twitter:title" content="[twitter-optimized title]" />

  <!-- Technical SEO -->
  <meta name="robots" content="index, follow" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="canonical" href="[canonical URL]" />

  <!-- Performance -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link
    rel="preload"
    href="[critical font]"
    as="font"
    type="font/woff2"
    crossorigin
  />
</head>
```

### Structured Data Schema

```json
{
  "@context": "https://schema.org",
  "@type": "[Organization/Product/Service/WebPage]",
  "name": "[Business/Product Name]",
  "description": "[SEO description]",
  "url": "[landing page URL]",
  "image": "[logo/product image URL]",
  "offers": {
    "@type": "Offer",
    "priceCurrency": "USD",
    "price": "[price if applicable]"
  }
}
```

### Page Architecture & Sections

```yaml
sections_order:
  1. Hero Section:
    - purpose: "Immediate value prop + primary CTA"
    - elements: "H1, subheading, CTA button, hero visual"
    - seo: "Primary keyword in H1, schema markup"

  2. Social Proof:
    - purpose: "Build trust and credibility"
    - elements: "Logos, testimonials, stats"
    - seo: "Review schema if applicable"

  3. Problem/Solution:
    - purpose: "Address pain points"
    - elements: "H2 with secondary keywords, benefit list"
    - seo: "FAQ schema for common questions"

  4. Features/Benefits:
    - purpose: "Detailed value proposition"
    - elements: "Feature cards, icons, descriptions"
    - seo: "Long-tail keywords in subheadings"

  5. CTA Section:
    - purpose: "Final conversion push"
    - elements: "Secondary CTA, urgency/scarcity"
    - seo: "Action-oriented keywords"

  6. Footer:
    - purpose: "SEO links, trust signals"
    - elements: "Contact info, privacy policy, sitemap"
    - seo: "Local business schema if applicable"
```

### Visual Design System

```css
/* Design tokens - customize for brand */
:root {
  /* Colors - ensure WCAG AA compliance */
  --primary: #[brand-primary];
  --secondary: #[brand-secondary];
  --accent: #[conversion-color]; /* high-contrast for CTAs */
  --text-primary: #[readable-dark];
  --text-secondary: #[readable-medium];
  --background: #[clean-background];

  /* Typography - performance optimized */
  --font-primary: "[primary-font]", system-ui, sans-serif;
  --font-secondary: "[secondary-font]", Georgia, serif;

  /* Spacing - consistent rhythm */
  --space-xs: 0.5rem;
  --space-sm: 1rem;
  --space-md: 2rem;
  --space-lg: 4rem;
  --space-xl: 8rem;

  /* Animations - subtle and purposeful */
  --transition-fast: 0.2s ease;
  --transition-medium: 0.3s ease;
  --transition-slow: 0.5s ease;
}
```

### Performance Optimization Checklist

```yaml
images:
  - format: "WebP with JPEG fallback"
  - sizes: "Multiple resolutions with srcset"
  - loading: "Lazy loading below fold"
  - optimization: "Compressed, proper alt text"

fonts:
  - loading: "font-display: swap"
  - preload: "Critical fonts only"
  - fallbacks: "System font fallbacks defined"

css:
  - critical: "Inline critical CSS"
  - non_critical: "Async load remaining CSS"
  - unused: "Remove unused styles"

javascript:
  - defer: "Non-critical JS deferred"
  - minify: "All JS minified and compressed"
  - bundle: "Code splitting for large bundles"
```

## Task Implementation Order

```yaml
Task 1 - SEO Foundation:
  CREATE index.html:
    - IMPLEMENT semantic HTML5 structure
    - ADD all meta tags and structured data
    - ENSURE proper heading hierarchy (H1 > H2 > H3)
    - VALIDATE markup with W3C validator

Task 2 - Performance Setup:
  OPTIMIZE assets:
    - COMPRESS and convert images to WebP
    - SETUP font loading strategy
    - CREATE critical CSS extraction
    - IMPLEMENT lazy loading

Task 3 - Hero Section:
  CREATE hero component:
    - IMPLEMENT above-fold critical content
    - ENSURE CLS optimization (reserve space)
    - ADD primary CTA with conversion tracking
    - TEST mobile responsiveness

Task 4 - Content Sections:
  BUILD remaining sections:
    - FOLLOW semantic HTML patterns
    - IMPLEMENT scroll animations (performance-conscious)
    - ADD conversion-focused microcopy
    - ENSURE keyword density balance

Task 5 - Conversion Optimization:
  IMPLEMENT tracking:
    - ADD Google Analytics/conversion pixels
    - SETUP form validation and submission
    - CREATE thank you page/modal
    - TEST conversion funnel

Task 6 - Mobile & Accessibility:
  OPTIMIZE user experience:
    - TEST all breakpoints (320px to 2560px)
    - ENSURE WCAG AA compliance
    - ADD keyboard navigation support
    - IMPLEMENT proper focus states
```

## Validation Loops

### Level 1: Technical SEO Audit

```bash
# SEO Validation Tools
lighthouse https://your-domain.com --view # Lighthouse audit
pagespeed insights: https://pagespeed.web.dev/
seobility: https://www.seobility.net/

# Expected Scores:
# Performance: >90 (Desktop), >80 (Mobile)
# SEO: >95
# Accessibility: >95
# Best Practices: >90
```

### Level 2: Design & UX Testing

```yaml
visual_testing:
  - cross_browser: "Chrome, Firefox, Safari, Edge"
  - devices: "iPhone, Android, iPad, Desktop"
  - breakpoints: "320px, 768px, 1024px, 1440px+"

conversion_testing:
  - cta_visibility: "Above fold, high contrast"
  - form_usability: "Simple, clear validation"
  - page_flow: "Logical content progression"

accessibility:
  - screen_reader: "Test with NVDA/JAWS"
  - keyboard_nav: "Tab through all elements"
  - color_contrast: "WCAG AA compliance"
```

### Level 3: Performance Validation

```bash
# Core Web Vitals Testing
npm install -g @lhci/cli
lhci autorun

# Expected Results:
# LCP: <2.5 seconds
# FID: <100 milliseconds
# CLS: <0.1

# Speed Testing
curl -o /dev/null -s -w "%{time_total}" https://your-domain.com
# Expected: <3 seconds total load time
```

### Level 4: SEO Validation

```yaml
keyword_check:
  - primary_keyword: "Appears in title, H1, first paragraph"
  - secondary_keywords: "Naturally integrated in H2s, content"
  - keyword_density: "1-3% for primary, avoid stuffing"

technical_seo:
  - meta_descriptions: "Unique, compelling, <155 characters"
  - title_tags: "Unique, keyword-rich, <60 characters"
  - structured_data: "Valid schema.org markup"
  - internal_links: "Logical anchor text, proper hierarchy"

indexability:
  - robots_txt: "Allows crawling of important pages"
  - sitemap: "XML sitemap submitted to Search Console"
  - canonical: "Proper canonical tags to avoid duplicates"
```

## Final Validation Checklist

### Performance & SEO

- [ ] Lighthouse scores meet targets (>90 Performance, >95 SEO)
- [ ] Core Web Vitals pass (LCP <2.5s, FID <100ms, CLS <0.1)
- [ ] Page loads in <3 seconds on 3G connection
- [ ] All images have proper alt text and are optimized
- [ ] Meta tags are complete and compelling
- [ ] Structured data validates without errors

### Design & UX

- [ ] Responsive design works across all devices
- [ ] CTAs are prominent and conversion-focused
- [ ] Typography is readable and on-brand
- [ ] Color contrast meets WCAG AA standards
- [ ] Animations are smooth and purpose-driven
- [ ] Forms are user-friendly with clear validation

### Conversion & Analytics

- [ ] Conversion tracking is properly implemented
- [ ] A/B testing framework is ready (if applicable)
- [ ] Heat mapping/user session recording enabled
- [ ] Thank you page/confirmation flow works
- [ ] Contact forms submit successfully

### Content & SEO

- [ ] Primary keywords naturally integrated
- [ ] Headlines are compelling and click-worthy
- [ ] Copy addresses target audience pain points
- [ ] Social proof elements are prominent
- [ ] Page content matches search intent

---

## Anti-Patterns to Avoid

### Performance

- ❌ Don't load heavy JavaScript above the fold
- ❌ Don't use auto-playing videos without optimization
- ❌ Don't load all images at once - implement lazy loading
- ❌ Don't ignore Core Web Vitals metrics

### SEO

- ❌ Don't keyword stuff - focus on natural integration
- ❌ Don't duplicate meta descriptions across pages
- ❌ Don't ignore mobile-first indexing requirements
- ❌ Don't forget structured data markup

### Design & UX

- ❌ Don't hide CTAs below the fold on mobile
- ❌ Don't use low-contrast text for readability
- ❌ Don't create forms with too many fields
- ❌ Don't ignore accessibility requirements
- ❌ Don't use generic stock photos - be authentic

### Conversion

- ❌ Don't overwhelm with multiple CTAs competing
- ❌ Don't make value proposition unclear or buried
- ❌ Don't ignore social proof and trust signals
- ❌ Don't create friction in the conversion process
