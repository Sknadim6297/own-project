{{--
  Professional Consultant Agreement (product brand: YuktiOne).
  Legal review flags (not inventable from config/yuktione.php):
  - Registered company / legal entity name is not configured; "Company" means the operator of the YuktiOne platform.
  - Jurisdiction, governing language, non-compete duration placeholders remain as in the prior agreement.
  - Street address / GST / registered office are not published in config (address is null).
--}}
@php
    $brandName = config('yuktione.name', 'YuktiOne');
    $noticeEmail = config('yuktione.email', 'info@yuktione.in');
@endphp
<div class="terms-agreement space-y-4 text-sm leading-6 text-text-secondary">
    <header class="space-y-2 border-b border-divider pb-4">
        <h4 class="text-base font-semibold text-brand-950 sm:text-lg">Consultant Online Agreement</h4>
        <p class="text-xs leading-5 text-text-muted">
            Product platform: <span class="font-medium text-brand-800">{{ $brandName }}</span>.
            Contracting party: the operator of the {{ $brandName }} platform (“Company”).
        </p>
    </header>

    <p>This Consultant Online Agreement (“Agreement”) is entered into on this [Date] by and between the operator of the {{ $brandName }} platform (“Company”), a platform providing online consulting services under the {{ $brandName }} brand, and the Consultant (“Consultant”). This Agreement governs the terms and conditions under which the Consultant provides services via the {{ $brandName }} platform.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">1. Scope of Agreement</h5>
    <p><strong>1.1. Services Provided:</strong> The Consultant agrees to provide professional consulting services in their respective field(s) of expertise, which may include, but are not limited to, astrology, interior design, education, job consultation, health consultation, and other areas listed on the {{ $brandName }} platform.</p>
    <p><strong>1.2.</strong> The Consultant is an independent contractor and not an employee, agent, or partner of the Company.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">2. Responsibilities of the Consultant</h5>
    <p><strong>2.1. Professional Standards:</strong> The Consultant agrees to deliver high-quality, accurate, and ethical consulting services to clients in accordance with industry standards and laws applicable to their field of expertise.</p>
    <p><strong>2.2. Communication:</strong> The Consultant shall promptly respond to client inquiries, provide clear and concise advice, and adhere to deadlines.</p>
    <p><strong>2.3. Confidentiality:</strong> The Consultant agrees to maintain the confidentiality of all client information obtained during consultations.</p>
    <p><strong>2.4. Compliance:</strong> The Consultant shall comply with all platform rules, company policies, and applicable laws, including those related to licensing or certifications.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">3. Company’s Responsibilities</h5>
    <p><strong>3.1. Platform Services:</strong> The Company will provide the Consultant with access to the {{ $brandName }} platform to connect with clients and manage appointments, communication, and payments.</p>
    <p><strong>3.2. Marketing and Support:</strong> The Company will promote consulting services and offer technical and administrative support to ensure seamless operation.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">4. Fees and Payments</h5>
    <p><strong>4.1. Service Fees:</strong> The Company will charge a commission on every successful consultation, as agreed upon during onboarding. The Consultant will receive the remaining balance after deducting this commission.</p>
    <p><strong>4.2. Payment Schedule:</strong> Payments will be processed on a bi-weekly or monthly basis (as specified) and transferred to the Consultant’s registered account.</p>
    <p><strong>4.3. Taxes:</strong> The Consultant is solely responsible for any taxes arising from their earnings on the platform.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">5. Use of Platform</h5>
    <p><strong>5.1.</strong> The Consultant agrees to use the {{ $brandName }} platform solely for providing consulting services and to refrain from any activities that could harm or misuse the platform.</p>
    <p><strong>5.2.</strong> The Consultant shall not solicit clients for off-platform engagements without prior written approval from the Company.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">6. Intellectual Property</h5>
    <p><strong>6.1.</strong> Any content or materials created for clients through the {{ $brandName }} platform (e.g., reports, designs, astrological charts) will remain the property of the client unless otherwise agreed.</p>
    <p><strong>6.2.</strong> The Company retains ownership of all platform-related materials, branding, and intellectual property.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">7. Confidentiality</h5>
    <p><strong>7.1.</strong> Both parties agree to keep confidential any proprietary or sensitive information shared during the course of this Agreement.</p>
    <p><strong>7.2.</strong> This obligation will survive termination of the Agreement.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">8. Term and Termination</h5>
    <p><strong>8.1. Term:</strong> This Agreement shall remain in effect unless terminated by either party with a 30-day written notice.</p>
    <p><strong>8.2. Termination for Cause:</strong> The Company may terminate this Agreement immediately if the Consultant violates platform policies, engages in misconduct, or receives repeated complaints from clients.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">9. Indemnification</h5>
    <p>The Consultant agrees to indemnify and hold harmless the Company, its employees, and its affiliates against any claims, damages, or liabilities arising from the Consultant’s actions, negligence, or breach of this Agreement.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">10. Dispute Resolution</h5>
    <p><strong>10.1.</strong> Any disputes between the Consultant and the Company shall be resolved through good faith negotiations.</p>
    <p><strong>10.2.</strong> If unresolved, disputes shall be submitted to arbitration in accordance with the laws of [Jurisdiction].</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">11. Limitation of Liability</h5>
    <p>The Company shall not be held liable for any damages or losses arising from:</p>
    <ul class="list-disc space-y-1 pl-5">
        <li>The Consultant’s failure to deliver services.</li>
        <li>Any technical issues or downtime of the platform.</li>
        <li>Miscommunication between the Consultant and clients.</li>
    </ul>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">12. Representations and Warranties</h5>
    <p><strong>12.1.</strong> The Consultant represents that they possess the necessary skills, qualifications, and, where applicable, licenses to provide consulting services.</p>
    <p><strong>12.2.</strong> The Consultant warrants that all information provided during onboarding is accurate and up to date.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">13. Governing Law</h5>
    <p>This Agreement shall be governed by and construed in accordance with the laws of [Jurisdiction].</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">14. Entire Agreement</h5>
    <p>This Agreement constitutes the entire understanding between the Consultant and the Company regarding the services and supersedes any prior agreements or communications.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">15. Amendments</h5>
    <p>The Company reserves the right to update this Agreement periodically. Consultants will be notified of significant changes, and continued use of the platform constitutes acceptance of the revised terms.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">17. Non-Compete and Non-Solicitation</h5>
    <p><strong>17.1. Non-Compete:</strong> The Consultant agrees that during the term of this Agreement and for a period of [6/12 months] after termination, they will not directly or indirectly:</p>
    <ul class="list-disc space-y-1 pl-5">
        <li>Offer similar consulting services through a competing platform that provides identical services to {{ $brandName }}.</li>
        <li>Use the Company’s client database for personal gain or to redirect business to other platforms or private engagements.</li>
    </ul>
    <p><strong>17.2. Non-Solicitation:</strong> The Consultant agrees not to solicit or attempt to solicit any of the Company’s clients, employees, or contractors for services outside the platform without the Company’s prior written consent.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">18. Data Privacy and Protection</h5>
    <p><strong>18.1.</strong> The Consultant acknowledges that all client data accessed via the {{ $brandName }} platform is strictly confidential and must be used solely for the purpose of providing consulting services.</p>
    <p><strong>18.2.</strong> The Consultant agrees to comply with the Company’s data privacy policies, including but not limited to:</p>
    <ul class="list-disc space-y-1 pl-5">
        <li>Not sharing, selling, or misusing client data.</li>
        <li>Storing client data securely to prevent unauthorized access.</li>
    </ul>
    <p><strong>18.3.</strong> Any breach of data privacy obligations may result in immediate termination and legal action.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">19. Quality Assurance and Training</h5>
    <p><strong>19.1.</strong> The Company reserves the right to periodically monitor the Consultant’s performance to ensure quality standards are upheld.</p>
    <p><strong>19.2.</strong> The Consultant may be required to attend training sessions, workshops, or webinars organized by the Company to improve service delivery and align with platform standards.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">20. Feedback and Ratings</h5>
    <p><strong>20.1.</strong> The Company will provide clients the option to rate and review the Consultant’s services. The Consultant acknowledges that these ratings and reviews are visible to other users and will impact their credibility on the platform.</p>
    <p><strong>20.2.</strong> The Consultant may respond to feedback in a professional manner but is prohibited from coercing or incentivizing clients for positive reviews.</p>
    <p><strong>20.3.</strong> Repeated negative reviews may result in suspension or termination of the Consultant’s account.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">21. Dispute Management with Clients</h5>
    <p><strong>21.1.</strong> In the event of a dispute between the Consultant and a client, the Consultant agrees to work collaboratively with the Company to resolve the matter amicably.</p>
    <p><strong>21.2.</strong> The Company will act as a mediator and have the final say in resolving disputes if an agreement cannot be reached.</p>
    <p><strong>21.3.</strong> Consultants are prohibited from threatening, harassing, or using abusive language towards clients under any circumstances.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">22. Liability and Indemnity</h5>
    <p><strong>22.1. Consultant’s Liability:</strong> The Consultant acknowledges that they are solely responsible for the accuracy and quality of the services provided to clients.</p>
    <p><strong>22.2. Indemnification:</strong> The Consultant agrees to indemnify and hold the Company harmless from any claims, liabilities, or damages arising from:</p>
    <ul class="list-disc space-y-1 pl-5">
        <li>Errors or omissions in the Consultant’s services.</li>
        <li>Breach of this Agreement by the Consultant.</li>
        <li>Any illegal or unethical actions taken by the Consultant during the provision of services.</li>
    </ul>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">23. Termination Procedures</h5>
    <p><strong>23.1. By the Company:</strong> The Company reserves the right to terminate this Agreement without notice if the Consultant:</p>
    <ul class="list-disc space-y-1 pl-5">
        <li>Violates any platform policies or terms of this Agreement.</li>
        <li>Engages in fraudulent, unethical, or illegal practices.</li>
        <li>Receives multiple valid complaints from clients.</li>
    </ul>
    <p><strong>23.2. By the Consultant:</strong> The Consultant may terminate this Agreement with a 30-day written notice. Any pending services or disputes must be resolved prior to termination.</p>
    <p><strong>23.3. Upon termination, the Consultant must:</strong></p>
    <ul class="list-disc space-y-1 pl-5">
        <li>Cease using the {{ $brandName }} platform and branding.</li>
        <li>Settle any outstanding dues with the Company.</li>
        <li>Return any Company-owned property, including documents, equipment, or software provided for the fulfillment of this Agreement.</li>
    </ul>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">24. Marketing and Publicity</h5>
    <p><strong>24.1.</strong> The Company reserves the right to use the Consultant’s name, photo, and professional profile for marketing and promotional activities related to the platform.</p>
    <p><strong>24.2.</strong> The Consultant agrees not to use the Company’s or {{ $brandName }} branding or name in external communications without prior approval.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">25. Amendments to Policies and Terms</h5>
    <p><strong>25.1.</strong> The Company reserves the right to modify this Agreement, platform policies, or service terms at any time.</p>
    <p><strong>25.2.</strong> Consultants will be notified of significant changes via email or platform notifications. Continued use of the platform constitutes acceptance of the revised terms.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">26. Force Majeure</h5>
    <p><strong>26.1.</strong> The Company shall not be held liable for any delay or failure to perform its obligations under this Agreement due to events beyond its reasonable control, including but not limited to natural disasters, government actions, labor strikes, or technical failures.</p>
    <p><strong>26.2.</strong> In the event of a force majeure situation, the affected party must notify the other party as soon as possible and make reasonable efforts to minimize the impact of the delay or non-performance.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">27. Severability</h5>
    <p>If any provision of this Agreement is found to be invalid or unenforceable, the remaining provisions shall remain in full force and effect.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">28. No Waiver</h5>
    <p>Any failure by either party to enforce any part of this Agreement shall not be construed as a waiver of the right to enforce that provision or any other provision at a later time.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">29. Governing Language</h5>
    <p>This Agreement is drafted in [English/other language]. In case of discrepancies between translations, the English version shall prevail.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">31. Insurance and Liability Coverage</h5>
    <p><strong>31.1.</strong> The Consultant is responsible for obtaining and maintaining any professional liability insurance, errors and omissions insurance, or any other coverage deemed necessary for the consulting services they provide.</p>
    <p><strong>31.2.</strong> Upon request, the Consultant must provide proof of such insurance to the Company. If the Consultant fails to do so, the Company reserves the right to terminate this Agreement.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">32. No Partnership or Joint Venture</h5>
    <p><strong>32.1.</strong> The Consultant is an independent contractor and is not an employee, partner, or joint venturer of the Company. Nothing in this Agreement shall be construed to create a partnership, joint venture, or employment relationship between the Consultant and the Company.</p>
    <p><strong>32.2.</strong> The Consultant has no authority to enter into any contracts or agreements on behalf of the Company or to bind the Company in any way.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">33. Social Media and Promotional Content</h5>
    <p><strong>33.1.</strong> The Consultant may be required to create promotional content such as posts, articles, or videos for the Company’s social media platforms to promote their services. The Consultant agrees to provide such content when requested.</p>
    <p><strong>33.2.</strong> Consultants must adhere to the Company’s branding guidelines while creating promotional content. The guidelines will be provided to the Consultant upon signing this Agreement.</p>
    <p><strong>33.3.</strong> All promotional content created by the Consultant for the Company must be reviewed and approved by the Company before being posted on any platform.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">34. Consultation Restrictions</h5>
    <p><strong>34.1.</strong> The Consultant agrees to only provide consultations in the areas of their expertise listed on the {{ $brandName }} platform. Any consultation outside of their designated services must be approved in writing by the Company.</p>
    <p><strong>34.2.</strong> The Consultant is prohibited from offering services that are illegal, unethical, or harmful in nature, including but not limited to: offering medical or legal advice without proper certification; providing services that promote hate speech, discrimination, or violence.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">35. Availability and Scheduling</h5>
    <p><strong>35.1.</strong> The Consultant agrees to keep their availability up-to-date on the platform and will make reasonable efforts to honor scheduled appointments.</p>
    <p><strong>35.2.</strong> If a Consultant needs to reschedule or cancel a consultation, they must inform the client and the Company at least [48 hours] in advance.</p>
    <p><strong>35.3.</strong> If a Consultant misses a scheduled consultation without prior notice, the Company reserves the right to issue a penalty fee or suspend the Consultant’s account.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">36. Client Refund Policy</h5>
    <p><strong>36.1.</strong> The Company may issue a full or partial refund to clients if they are dissatisfied with the services rendered by the Consultant. The decision to issue a refund is at the Company’s discretion.</p>
    <p><strong>36.2.</strong> If a refund is issued due to the Consultant’s failure to deliver services or providing substandard services, the Consultant may be required to reimburse the Company for the refund amount.</p>
    <p><strong>36.3.</strong> In case of a dispute regarding refunds, the Company will act as the arbitrator between the Consultant and the client.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">37. Monitoring and Audits</h5>
    <p><strong>37.1.</strong> The Company reserves the right to monitor the Consultant’s performance, including client feedback, consultation success rates, and platform compliance.</p>
    <p><strong>37.2.</strong> The Company may conduct periodic audits of the Consultant’s activity to ensure compliance with platform standards.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">38. Platform Usage and Restrictions</h5>
    <p>The Consultant agrees to use the platform solely for professional services and not engage in disruptive or illegal activities.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">39. Third-Party Services and Content</h5>
    <p>The Company may integrate third-party tools. The Consultant agrees to comply with third-party terms and hold the Company harmless for third-party issues.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">40. Exit Strategy and Deactivation</h5>
    <p>Either party may deactivate; outstanding payments will be settled and data retention handled according to policy.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">41. Relationship with Clients</h5>
    <p>Client relationships are managed through {{ $brandName }}; direct contact for external engagements is not permitted without written approval.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">42. Entire Agreement and Modifications</h5>
    <p>This Agreement constitutes the entire understanding and may only be modified in writing signed by both parties.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">43. Waiver of Rights</h5>
    <p>Failure to enforce a provision is not a waiver of future enforcement rights.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">44. Assignment</h5>
    <p>The Consultant may not assign their rights without the Company’s consent. The Company may assign rights to third parties.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">45. Additional Clauses for Specialized Consultants</h5>
    <p>Special rules may apply to Health, Job, and Astrology consultants as described in the platform policies.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">46. Dispute Resolution</h5>
    <p>Disputes will first attempt mediation, then binding arbitration, with jurisdiction and organization specified in the Agreement.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">47. Confidentiality and Non-Disclosure</h5>
    <p>The Consultant agrees to keep confidential proprietary information and return or destroy such information upon termination.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">48. Termination by Consultant</h5>
    <p>The Consultant may terminate with 30 days written notice and must complete obligations prior to termination.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">49. Compliance with Laws</h5>
    <p>The Consultant must comply with all applicable laws and international regulations when serving clients in other countries.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">50. Indemnity</h5>
    <p>Both parties agree to indemnify the other for breaches as described in this Agreement.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">51. Force Majeure</h5>
    <p>Neither party is liable for performance failures caused by events beyond their reasonable control.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">52. Technology and Platform Updates</h5>
    <p>The Company may update the platform; the Consultant must ensure compatibility of their devices and connectivity.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">53. Intellectual Property Ownership</h5>
    <p>The Company retains ownership of platform content; the Consultant grants the Company a license to use Consultant-generated content for promotion.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">54. Non-Solicitation</h5>
    <p>The Consultant agrees not to solicit clients or Company staff for 12 months following termination.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">55. Client Relationship Management</h5>
    <p>The Consultant must communicate professionally and report complaints to the Company when necessary.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">56. Successor Agreements and Continuity</h5>
    <p>The Company may enter successor agreements that supersede this Agreement.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">57. Miscellaneous</h5>
    <p>Standard contractual provisions such as severability, headings, and survival clauses apply.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">58. Notices</h5>
    <p>All notices must be sent to the Company at <a href="mailto:{{ $noticeEmail }}" class="font-medium text-brand-primary underline-offset-2 hover:underline">{{ $noticeEmail }}</a> and the Consultant at their registered contact details.</p>

    <h5 class="pt-1 text-sm font-semibold text-brand-950">59. Acknowledgment and Acceptance</h5>
    <p>By clicking “I Agree” or signing, the Consultant acknowledges that they have read and accept this Agreement.</p>

    <div class="mt-4 space-y-4 rounded-control border border-divider bg-surface-muted p-3 sm:p-4">
        <div class="space-y-1">
            <p class="text-sm font-semibold text-brand-950">Company Representative</p>
            <p>Name: ________________________</p>
            <p>Title: ________________________</p>
            <p>Signature: _____________________</p>
            <p>Date: ________________________</p>
        </div>
        <div class="space-y-1 border-t border-divider pt-3">
            <p class="text-sm font-semibold text-brand-950">Consultant</p>
            <p>Name: ________________________</p>
            <p>Signature: _____________________</p>
            <p>Date: ________________________</p>
        </div>
    </div>
</div>
