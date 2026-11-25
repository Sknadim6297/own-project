# Task 46 audit notes

## Root cause
Inflation SVG chart showed only "Today" / "Future" category labels on two bars. No on-graph financial values, no Y-axis currency ticks, no year-wise intermediate points.

## Fix
`InflationChart` builds year-wise future-cost points with `PfmMath::compound`, final point pinned to engine `future_cost`. SVG line chart + compact axis labels + `<title>` tooltips + year-wise table.
