<?php

/**
 * Ceremonial county => ONS local authority districts it is made of.
 *
 * Ceremonial counties are built from whole districts, except Stockton-on-Tees, whose
 * south bank (TS15, TS17) lies in North Yorkshire; `split` resolves it by outcode.
 * Districts abolished since 2018 are kept so older ONS data in a response still resolves.
 */
return [
    'districts' => [
        'Bedfordshire' => ['Bedford', 'Central Bedfordshire', 'Luton'],
        'Berkshire' => ['Bracknell Forest', 'Reading', 'Slough', 'West Berkshire', 'Windsor and Maidenhead', 'Wokingham'],
        'Bristol' => ['Bristol, City of', 'Bristol'],
        'Buckinghamshire' => ['Buckinghamshire', 'Milton Keynes', 'Aylesbury Vale', 'Chiltern', 'South Bucks', 'Wycombe'],
        'Cambridgeshire' => ['Cambridge', 'East Cambridgeshire', 'Fenland', 'Huntingdonshire', 'Peterborough', 'South Cambridgeshire'],
        'Cheshire' => ['Cheshire East', 'Cheshire West and Chester', 'Halton', 'Warrington'],
        'City of London' => ['City of London'],
        'Cornwall' => ['Cornwall', 'Isles of Scilly'],
        'Cumbria' => ['Cumberland', 'Westmorland and Furness', 'Allerdale', 'Barrow-in-Furness', 'Carlisle', 'Copeland', 'Eden', 'South Lakeland'],
        'Derbyshire' => ['Amber Valley', 'Bolsover', 'Chesterfield', 'Derby', 'Derbyshire Dales', 'Erewash', 'High Peak', 'North East Derbyshire', 'South Derbyshire'],
        'Devon' => ['East Devon', 'Exeter', 'Mid Devon', 'North Devon', 'Plymouth', 'South Hams', 'Teignbridge', 'Torbay', 'Torridge', 'West Devon'],
        'Dorset' => ['Bournemouth, Christchurch and Poole', 'Dorset', 'Bournemouth', 'Christchurch', 'East Dorset', 'North Dorset', 'Poole', 'Purbeck', 'West Dorset', 'Weymouth and Portland'],
        'Durham' => ['County Durham', 'Darlington', 'Hartlepool', 'Stockton-on-Tees'],
        'East Riding of Yorkshire' => ['East Riding of Yorkshire', 'Kingston upon Hull, City of', 'Kingston upon Hull'],
        'East Sussex' => ['Brighton and Hove', 'Eastbourne', 'Hastings', 'Lewes', 'Rother', 'Wealden'],
        'Essex' => ['Basildon', 'Braintree', 'Brentwood', 'Castle Point', 'Chelmsford', 'Colchester', 'Epping Forest', 'Harlow', 'Maldon', 'Rochford', 'Southend-on-Sea', 'Tendring', 'Thurrock', 'Uttlesford'],
        'Gloucestershire' => ['Cheltenham', 'Cotswold', 'Forest of Dean', 'Gloucester', 'South Gloucestershire', 'Stroud', 'Tewkesbury'],
        'Greater London' => [
            'Barking and Dagenham', 'Barnet', 'Bexley', 'Brent', 'Bromley', 'Camden', 'Croydon', 'Ealing', 'Enfield', 'Greenwich',
            'Hackney', 'Hammersmith and Fulham', 'Haringey', 'Harrow', 'Havering', 'Hillingdon', 'Hounslow', 'Islington',
            'Kensington and Chelsea', 'Kingston upon Thames', 'Lambeth', 'Lewisham', 'Merton', 'Newham', 'Redbridge',
            'Richmond upon Thames', 'Southwark', 'Sutton', 'Tower Hamlets', 'Waltham Forest', 'Wandsworth', 'Westminster',
        ],
        'Greater Manchester' => ['Bolton', 'Bury', 'Manchester', 'Oldham', 'Rochdale', 'Salford', 'Stockport', 'Tameside', 'Trafford', 'Wigan'],
        'Hampshire' => ['Basingstoke and Deane', 'East Hampshire', 'Eastleigh', 'Fareham', 'Gosport', 'Hart', 'Havant', 'New Forest', 'Portsmouth', 'Rushmoor', 'Southampton', 'Test Valley', 'Winchester'],
        'Herefordshire' => ['Herefordshire, County of', 'Herefordshire'],
        'Hertfordshire' => ['Broxbourne', 'Dacorum', 'East Hertfordshire', 'Hertsmere', 'North Hertfordshire', 'St Albans', 'Stevenage', 'Three Rivers', 'Watford', 'Welwyn Hatfield'],
        'Isle of Wight' => ['Isle of Wight'],
        'Kent' => ['Ashford', 'Canterbury', 'Dartford', 'Dover', 'Folkestone and Hythe', 'Shepway', 'Gravesham', 'Maidstone', 'Medway', 'Sevenoaks', 'Swale', 'Thanet', 'Tonbridge and Malling', 'Tunbridge Wells'],
        'Lancashire' => ['Blackburn with Darwen', 'Blackpool', 'Burnley', 'Chorley', 'Fylde', 'Hyndburn', 'Lancaster', 'Pendle', 'Preston', 'Ribble Valley', 'Rossendale', 'South Ribble', 'West Lancashire', 'Wyre'],
        'Leicestershire' => ['Blaby', 'Charnwood', 'Harborough', 'Hinckley and Bosworth', 'Leicester', 'Melton', 'North West Leicestershire', 'Oadby and Wigston'],
        'Lincolnshire' => ['Boston', 'East Lindsey', 'Lincoln', 'North East Lincolnshire', 'North Kesteven', 'North Lincolnshire', 'South Holland', 'South Kesteven', 'West Lindsey'],
        'Merseyside' => ['Knowsley', 'Liverpool', 'Sefton', 'St Helens', 'Wirral'],
        'Norfolk' => ['Breckland', 'Broadland', 'Great Yarmouth', "King's Lynn and West Norfolk", 'North Norfolk', 'Norwich', 'South Norfolk'],
        'North Yorkshire' => ['North Yorkshire', 'Middlesbrough', 'Redcar and Cleveland', 'York', 'Craven', 'Hambleton', 'Harrogate', 'Richmondshire', 'Ryedale', 'Scarborough', 'Selby'],
        'Northamptonshire' => ['North Northamptonshire', 'West Northamptonshire', 'Corby', 'Daventry', 'East Northamptonshire', 'Kettering', 'Northampton', 'South Northamptonshire', 'Wellingborough'],
        'Northumberland' => ['Northumberland'],
        'Nottinghamshire' => ['Ashfield', 'Bassetlaw', 'Broxtowe', 'Gedling', 'Mansfield', 'Newark and Sherwood', 'Nottingham', 'Rushcliffe'],
        'Oxfordshire' => ['Cherwell', 'Oxford', 'South Oxfordshire', 'Vale of White Horse', 'West Oxfordshire'],
        'Rutland' => ['Rutland'],
        'Shropshire' => ['Shropshire', 'Telford and Wrekin'],
        'Somerset' => ['Somerset', 'Bath and North East Somerset', 'North Somerset', 'Mendip', 'Sedgemoor', 'Somerset West and Taunton', 'South Somerset', 'Taunton Deane', 'West Somerset'],
        'South Yorkshire' => ['Barnsley', 'Doncaster', 'Rotherham', 'Sheffield'],
        'Staffordshire' => ['Cannock Chase', 'East Staffordshire', 'Lichfield', 'Newcastle-under-Lyme', 'South Staffordshire', 'Stafford', 'Staffordshire Moorlands', 'Stoke-on-Trent', 'Tamworth'],
        'Suffolk' => ['Babergh', 'East Suffolk', 'Ipswich', 'Mid Suffolk', 'West Suffolk', 'Forest Heath', 'St Edmundsbury', 'Suffolk Coastal', 'Waveney'],
        'Surrey' => ['Elmbridge', 'Epsom and Ewell', 'Guildford', 'Mole Valley', 'Reigate and Banstead', 'Runnymede', 'Spelthorne', 'Surrey Heath', 'Tandridge', 'Waverley', 'Woking'],
        'Tyne and Wear' => ['Gateshead', 'Newcastle upon Tyne', 'North Tyneside', 'South Tyneside', 'Sunderland'],
        'Warwickshire' => ['North Warwickshire', 'Nuneaton and Bedworth', 'Rugby', 'Stratford-on-Avon', 'Warwick'],
        'West Midlands' => ['Birmingham', 'Coventry', 'Dudley', 'Sandwell', 'Solihull', 'Walsall', 'Wolverhampton'],
        'West Sussex' => ['Adur', 'Arun', 'Chichester', 'Crawley', 'Horsham', 'Mid Sussex', 'Worthing'],
        'West Yorkshire' => ['Bradford', 'Calderdale', 'Kirklees', 'Leeds', 'Wakefield'],
        'Wiltshire' => ['Swindon', 'Wiltshire'],
        'Worcestershire' => ['Bromsgrove', 'Malvern Hills', 'Redditch', 'Worcester', 'Wychavon', 'Wyre Forest'],
    ],
    'split' => [
        'Stockton-on-Tees' => ['TS15' => 'North Yorkshire', 'TS17' => 'North Yorkshire'],
    ],
];
